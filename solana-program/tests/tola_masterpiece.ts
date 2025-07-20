import * as anchor from "@coral-xyz/anchor";
import { Program } from "@coral-xyz/anchor";
import { TolaMasterpiece } from "../target/types/tola_masterpiece";
import { PublicKey, Keypair, SystemProgram, SYSVAR_RENT_PUBKEY } from "@solana/web3.js";
import { TOKEN_PROGRAM_ID, ASSOCIATED_TOKEN_PROGRAM_ID } from "@solana/spl-token";
import { expect } from "chai";

describe("tola_masterpiece", () => {
  const provider = anchor.AnchorProvider.env();
  anchor.setProvider(provider);

  const program = anchor.workspace.TolaMasterpiece as Program<TolaMasterpiece>;
  const authority = provider.wallet.publicKey;
  
  // Test accounts
  let programState: PublicKey;
  let programStateBump: number;
  
  let artworkAccount: PublicKey;
  let artworkBump: number;
  
  let mint: Keypair;
  let recipient: Keypair;
  
  const METADATA_PROGRAM_ID = new PublicKey("metaqbxxUerdq28cj1RbAWkYQm3ybzjb6a8bt518x1s");
  
  before(async () => {
    // Find program state PDA
    [programState, programStateBump] = await PublicKey.findProgramAddress(
      [Buffer.from("program_state")],
      program.programId
    );
    
    // Create test accounts
    mint = Keypair.generate();
    recipient = Keypair.generate();
    
    // Airdrop SOL to recipient
    await provider.connection.confirmTransaction(
      await provider.connection.requestAirdrop(recipient.publicKey, 1000000000),
      "confirmed"
    );
  });

  it("Initializes the program", async () => {
    const tx = await program.methods
      .initialize(programStateBump)
      .accounts({
        programState,
        authority,
        systemProgram: SystemProgram.programId,
      })
      .rpc();

    console.log("Initialize transaction signature:", tx);

    // Verify program state
    const programStateAccount = await program.account.programState.fetch(programState);
    expect(programStateAccount.authority.toString()).to.equal(authority.toString());
    expect(programStateAccount.totalMinted.toNumber()).to.equal(0);
    expect(programStateAccount.royaltyFee).to.equal(500);
    expect(programStateAccount.maxSecondaryRoyalty).to.equal(1500);
    expect(programStateAccount.isPaused).to.equal(false);
  });

  it("Mints a new artwork NFT", async () => {
    const artworkId = new anchor.BN(1);
    
    // Find artwork account PDA
    [artworkAccount, artworkBump] = await PublicKey.findProgramAddress(
      [Buffer.from("artwork"), artworkId.toArrayLike(Buffer, "le", 8)],
      program.programId
    );
    
    // Find metadata account
    const [metadataAccount] = await PublicKey.findProgramAddress(
      [
        Buffer.from("metadata"),
        METADATA_PROGRAM_ID.toBuffer(),
        mint.publicKey.toBuffer(),
      ],
      METADATA_PROGRAM_ID
    );
    
    // Find master edition account
    const [masterEdition] = await PublicKey.findProgramAddress(
      [
        Buffer.from("metadata"),
        METADATA_PROGRAM_ID.toBuffer(),
        mint.publicKey.toBuffer(),
        Buffer.from("edition"),
      ],
      METADATA_PROGRAM_ID
    );
    
    // Find associated token account
    const [associatedTokenAccount] = await PublicKey.findProgramAddress(
      [
        recipient.publicKey.toBuffer(),
        TOKEN_PROGRAM_ID.toBuffer(),
        mint.publicKey.toBuffer(),
      ],
      ASSOCIATED_TOKEN_PROGRAM_ID
    );
    
    // Create creators array
    const creators = [
      {
        address: authority,
        verified: true,
        share: 100,
      },
    ];
    
    const tx = await program.methods
      .mintArtwork(
        artworkId,
        "TOLA Masterpiece #1",
        "TOLA",
        "https://arweave.net/artwork1",
        creators
      )
      .accounts({
        programState,
        artworkAccount,
        mint: mint.publicKey,
        mintAuthority: authority,
        associatedTokenAccount,
        metadataAccount,
        masterEdition,
        metadataProgram: METADATA_PROGRAM_ID,
        recipient: recipient.publicKey,
        payer: authority,
        tokenProgram: TOKEN_PROGRAM_ID,
        associatedTokenProgram: ASSOCIATED_TOKEN_PROGRAM_ID,
        systemProgram: SystemProgram.programId,
        rent: SYSVAR_RENT_PUBKEY,
      })
      .signers([mint, recipient])
      .rpc();

    console.log("Mint artwork transaction signature:", tx);

    // Verify artwork account
    const artworkAccountData = await program.account.artworkAccount.fetch(artworkAccount);
    expect(artworkAccountData.artworkId.toNumber()).to.equal(1);
    expect(artworkAccountData.mint.toString()).to.equal(mint.publicKey.toString());
    expect(artworkAccountData.creator.toString()).to.equal(recipient.publicKey.toString());
    expect(artworkAccountData.name).to.equal("TOLA Masterpiece #1");
    expect(artworkAccountData.royaltyFee).to.equal(500);
    
    // Verify program state updated
    const programStateAccount = await program.account.programState.fetch(programState);
    expect(programStateAccount.totalMinted.toNumber()).to.equal(1);
  });

  it("Sets custom royalty for artwork", async () => {
    const newRoyaltyFee = 1000; // 10%
    const royaltyRecipient = recipient.publicKey;
    
    // Find metadata account
    const [metadataAccount] = await PublicKey.findProgramAddress(
      [
        Buffer.from("metadata"),
        METADATA_PROGRAM_ID.toBuffer(),
        mint.publicKey.toBuffer(),
      ],
      METADATA_PROGRAM_ID
    );
    
    const tx = await program.methods
      .setArtworkRoyalty(newRoyaltyFee, royaltyRecipient)
      .accounts({
        programState,
        artworkAccount,
        metadataAccount,
        metadataProgram: METADATA_PROGRAM_ID,
        currentOwner: recipient.publicKey,
      })
      .signers([recipient])
      .rpc();

    console.log("Set royalty transaction signature:", tx);

    // Verify artwork account updated
    const artworkAccountData = await program.account.artworkAccount.fetch(artworkAccount);
    expect(artworkAccountData.royaltyFee).to.equal(newRoyaltyFee);
    expect(artworkAccountData.royaltyRecipient.toString()).to.equal(royaltyRecipient.toString());
  });

  it("Gets artwork info", async () => {
    const tx = await program.methods
      .getArtworkInfo()
      .accounts({
        artworkAccount,
      })
      .rpc();

    console.log("Get artwork info transaction signature:", tx);
  });

  it("Updates program settings", async () => {
    const newRoyaltyFee = 600; // 6%
    const newMaxSecondaryRoyalty = 1200; // 12%
    
    const tx = await program.methods
      .updateProgramSettings(newRoyaltyFee, newMaxSecondaryRoyalty, false)
      .accounts({
        programState,
        authority,
      })
      .rpc();

    console.log("Update settings transaction signature:", tx);

    // Verify program state updated
    const programStateAccount = await program.account.programState.fetch(programState);
    expect(programStateAccount.royaltyFee).to.equal(newRoyaltyFee);
    expect(programStateAccount.maxSecondaryRoyalty).to.equal(newMaxSecondaryRoyalty);
  });

  it("Fails to set royalty fee above maximum", async () => {
    const excessiveRoyaltyFee = 2000; // 20% - should fail
    const royaltyRecipient = recipient.publicKey;
    
    try {
      await program.methods
        .setArtworkRoyalty(excessiveRoyaltyFee, royaltyRecipient)
        .accounts({
          programState,
          artworkAccount,
          metadataAccount: PublicKey.default,
          metadataProgram: METADATA_PROGRAM_ID,
          currentOwner: recipient.publicKey,
        })
        .signers([recipient])
        .rpc();
      
      expect.fail("Should have thrown an error");
    } catch (error) {
      expect(error.message).to.include("RoyaltyFeeTooHigh");
    }
  });

  it("Fails to mint when program is paused", async () => {
    // Pause the program
    await program.methods
      .updateProgramSettings(null, null, true)
      .accounts({
        programState,
        authority,
      })
      .rpc();

    const artworkId = new anchor.BN(2);
    const newMint = Keypair.generate();
    
    const [newArtworkAccount] = await PublicKey.findProgramAddress(
      [Buffer.from("artwork"), artworkId.toArrayLike(Buffer, "le", 8)],
      program.programId
    );
    
    const [metadataAccount] = await PublicKey.findProgramAddress(
      [
        Buffer.from("metadata"),
        METADATA_PROGRAM_ID.toBuffer(),
        newMint.publicKey.toBuffer(),
      ],
      METADATA_PROGRAM_ID
    );
    
    const [masterEdition] = await PublicKey.findProgramAddress(
      [
        Buffer.from("metadata"),
        METADATA_PROGRAM_ID.toBuffer(),
        newMint.publicKey.toBuffer(),
        Buffer.from("edition"),
      ],
      METADATA_PROGRAM_ID
    );
    
    const [associatedTokenAccount] = await PublicKey.findProgramAddress(
      [
        recipient.publicKey.toBuffer(),
        TOKEN_PROGRAM_ID.toBuffer(),
        newMint.publicKey.toBuffer(),
      ],
      ASSOCIATED_TOKEN_PROGRAM_ID
    );
    
    const creators = [
      {
        address: authority,
        verified: true,
        share: 100,
      },
    ];
    
    try {
      await program.methods
        .mintArtwork(
          artworkId,
          "TOLA Masterpiece #2",
          "TOLA",
          "https://arweave.net/artwork2",
          creators
        )
        .accounts({
          programState,
          artworkAccount: newArtworkAccount,
          mint: newMint.publicKey,
          mintAuthority: authority,
          associatedTokenAccount,
          metadataAccount,
          masterEdition,
          metadataProgram: METADATA_PROGRAM_ID,
          recipient: recipient.publicKey,
          payer: authority,
          tokenProgram: TOKEN_PROGRAM_ID,
          associatedTokenProgram: ASSOCIATED_TOKEN_PROGRAM_ID,
          systemProgram: SystemProgram.programId,
          rent: SYSVAR_RENT_PUBKEY,
        })
        .signers([newMint, recipient])
        .rpc();
      
      expect.fail("Should have thrown an error");
    } catch (error) {
      expect(error.message).to.include("ProgramPaused");
    }
  });
}); 
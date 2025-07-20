use anchor_lang::prelude::*;
use anchor_spl::{
    associated_token::AssociatedToken,
    token::{self, Mint, Token, TokenAccount, Transfer},
};
use mpl_token_metadata::{
    instruction::{create_metadata_accounts_v3, create_master_edition_v3, update_metadata_accounts_v2},
    state::{DataV2, Creator, Collection, Uses},
};
use solana_program::{
    program::{invoke, invoke_signed},
    system_instruction,
};
use std::collections::HashMap;

declare_id!("Fg6PaFpoGXkYsidMpWTK6W2BeZ7FEfcYkg476zPFsLnS");

#[program]
pub mod tola_masterpiece {
    use super::*;

    /// Calculate artist royalty ratio based on ranking score and total artworks
    fn calculate_artist_royalty_ratio(ranking_score: u64, artist_artworks: u64, total_artworks: u64) -> u16 {
        if total_artworks == 0 {
            return 0;
        }
        
        // Calculate ratio based on:
        // 1. Artist's share of total artworks (50% weight)
        // 2. Ranking score relative to maximum possible score (50% weight)
        
        let artwork_ratio = (artist_artworks as f64 / total_artworks as f64) * 0.5;
        let ranking_ratio = (ranking_score as f64 / 10000.0) * 0.5; // Assuming max ranking score is 10000
        
        let total_ratio = artwork_ratio + ranking_ratio;
        
        // Convert to basis points (0-10000)
        let basis_points = (total_ratio * 10000.0) as u16;
        
        // Ensure it doesn't exceed 100%
        std::cmp::min(basis_points, 10000)
    }

    /// Calculate individual artist shares for dynamic royalty distribution
    fn calculate_artist_shares(participating_artists: &[Pubkey], program_state: &ProgramState) -> Result<Vec<u16>> {
        let mut artist_shares = Vec::new();
        let mut total_ratio = 0u64;
        
        // Calculate total ratio of all participating artists
        for artist in participating_artists {
            // Calculate artist ratio based on their position in the list and total artworks
            // This simulates the ranking-based calculation until we implement cross-account lookup
            let artist_index = participating_artists.iter().position(|&x| x == *artist).unwrap_or(0);
            let base_ratio = 10000u64 / participating_artists.len() as u64;
            
            // Apply ranking multiplier based on position (higher position = higher ratio)
            let ranking_multiplier = 100 + (artist_index as u64 * 10); // 100% to 190% based on position
            let artist_ratio = (base_ratio * ranking_multiplier) / 100;
            
            total_ratio += artist_ratio;
        }
        
        // Calculate individual shares based on calculated ratios
        for artist in participating_artists {
            let artist_index = participating_artists.iter().position(|&x| x == *artist).unwrap_or(0);
            let base_ratio = 10000u64 / participating_artists.len() as u64;
            let ranking_multiplier = 100 + (artist_index as u64 * 10);
            let artist_ratio = (base_ratio * ranking_multiplier) / 100;
            
            let share = if total_ratio > 0 {
                ((artist_ratio * 1500) / total_ratio) as u16 // 15% distributed by ratio
            } else {
                0
            };
            artist_shares.push(share);
        }
        
        Ok(artist_shares)
    }

    /// Initialize the TOLA masterpiece program
    pub fn initialize(ctx: Context<Initialize>, bump: u8) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        program_state.authority = ctx.accounts.authority.key();
        program_state.bump = bump;
        program_state.total_minted = 0;
        program_state.royalty_fee = 500; // 5% default royalty
        program_state.max_secondary_royalty = 1500; // 15% maximum secondary royalty
        program_state.is_paused = false;
        program_state.huraii_system_owner = ctx.accounts.authority.key(); // Initially set to authority
        program_state.total_artworks_published = 0;
        program_state.dynamic_royalty_enabled = true; // Enable dynamic royalty by default
        
        msg!("TOLA Masterpiece program initialized");
        msg!("Authority: {}", program_state.authority);
        msg!("HURAII System Owner: {}", program_state.huraii_system_owner);
        msg!("Default royalty fee: {}%", program_state.royalty_fee as f64 / 100.0);
        msg!("Dynamic royalty enabled: {}", program_state.dynamic_royalty_enabled);
        
        Ok(())
    }

    /// Mint a new TOLA masterpiece NFT
    pub fn mint_artwork(
        ctx: Context<MintArtwork>,
        artwork_id: u64,
        name: String,
        symbol: String,
        uri: String,
        creators: Vec<mpl_token_metadata::state::Creator>,
    ) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        
        // Check if program is paused
        require!(!program_state.is_paused, ErrorCode::ProgramPaused);
        
        // Validate input
        require!(name.len() <= 32, ErrorCode::NameTooLong);
        require!(symbol.len() <= 10, ErrorCode::SymbolTooLong);
        require!(uri.len() <= 200, ErrorCode::UriTooLong);
        
        // Create mint account
        let mint_account = &ctx.accounts.mint;
        let mint_authority = &ctx.accounts.mint_authority;
        let payer = &ctx.accounts.payer;
        let system_program = &ctx.accounts.system_program;
        let token_program = &ctx.accounts.token_program;
        let rent = &ctx.accounts.rent;

        // Create mint
        let mint_rent = rent.minimum_balance(token::Mint::LEN);
        invoke(
            &system_instruction::create_account(
                payer.key,
                mint_account.key,
                mint_rent,
                token::Mint::LEN as u64,
                token_program.key,
            ),
            &[payer.to_account_info(), mint_account.to_account_info(), system_program.to_account_info()],
        )?;

        // Initialize mint
        invoke(
            &spl_token::instruction::initialize_mint(
                token_program.key,
                mint_account.key,
                mint_authority.key,
                Some(mint_authority.key),
                0,
            )?,
            &[
                mint_account.to_account_info(),
                rent.to_account_info(),
                token_program.to_account_info(),
            ],
        )?;

        // Create associated token account for recipient
        let associated_token_account = &ctx.accounts.associated_token_account;
        let recipient = &ctx.accounts.recipient;
        
        invoke(
            &spl_associated_token_account::instruction::create_associated_token_account(
                payer.key,
                recipient.key,
                mint_account.key,
                token_program.key,
            ),
            &[
                payer.to_account_info(),
                associated_token_account.to_account_info(),
                recipient.to_account_info(),
                mint_account.to_account_info(),
                system_program.to_account_info(),
                token_program.to_account_info(),
            ],
        )?;

        // Mint token to recipient
        invoke(
            &spl_token::instruction::mint_to(
                token_program.key,
                mint_account.key,
                associated_token_account.key,
                mint_authority.key,
                &[],
                1,
            )?,
            &[
                mint_account.to_account_info(),
                associated_token_account.to_account_info(),
                mint_authority.to_account_info(),
                token_program.to_account_info(),
            ],
        )?;

        // Create metadata account
        let metadata_account = &ctx.accounts.metadata_account;
        let metadata_program = &ctx.accounts.metadata_program;
        
        // Prepare metadata
        let data = DataV2 {
            name: name.clone(),
            symbol: symbol.clone(),
            uri: uri.clone(),
            seller_fee_basis_points: program_state.royalty_fee,
            creators: Some(creators),
            collection: None,
            uses: None,
        };

        invoke(
            &create_metadata_accounts_v3(
                metadata_program.key(),
                metadata_account.key(),
                mint_account.key(),
                mint_authority.key(),
                payer.key(),
                mint_authority.key(),
                name.clone(),
                symbol.clone(),
                uri.clone(),
                Some(creators),
                program_state.royalty_fee,
                false,
                true,
                None,
                None,
                None,
            ),
            &[
                metadata_account.to_account_info(),
                mint_account.to_account_info(),
                mint_authority.to_account_info(),
                payer.to_account_info(),
                mint_authority.to_account_info(),
                system_program.to_account_info(),
                rent.to_account_info(),
            ],
        )?;

        // Create master edition
        let master_edition = &ctx.accounts.master_edition;
        invoke(
            &create_master_edition_v3(
                metadata_program.key(),
                master_edition.key(),
                mint_account.key(),
                mint_authority.key(),
                mint_authority.key(),
                metadata_account.key(),
                payer.key(),
                Some(0),
            ),
            &[
                master_edition.to_account_info(),
                mint_account.to_account_info(),
                mint_authority.to_account_info(),
                mint_authority.to_account_info(),
                payer.to_account_info(),
                metadata_account.to_account_info(),
                token_program.to_account_info(),
                system_program.to_account_info(),
                rent.to_account_info(),
            ],
        )?;

        // Initialize artwork account
        let artwork_account = &mut ctx.accounts.artwork_account;
        artwork_account.artwork_id = artwork_id;
        artwork_account.mint = mint_account.key();
        artwork_account.creator = recipient.key();
        artwork_account.current_owner = recipient.key();
        artwork_account.name = name;
        artwork_account.symbol = symbol;
        artwork_account.uri = uri;
        artwork_account.royalty_fee = program_state.royalty_fee;
        artwork_account.royalty_recipient = program_state.authority;
        artwork_account.created_at = Clock::get()?.unix_timestamp;
        artwork_account.bump = *ctx.bumps.get("artwork_account").unwrap();

        // Update program state
        program_state.total_minted += 1;

        msg!("TOLA Masterpiece NFT minted successfully");
        msg!("Artwork ID: {}", artwork_id);
        msg!("Mint: {}", mint_account.key());
        msg!("Creator: {}", recipient.key());
        msg!("Name: {}", artwork_account.name);
        
        Ok(())
    }

    /// Set custom royalty for a specific artwork (up to 15%)
    pub fn set_artwork_royalty(
        ctx: Context<SetArtworkRoyalty>,
        new_royalty_fee: u16,
        royalty_recipient: Pubkey,
    ) -> Result<()> {
        let program_state = &ctx.accounts.program_state;
        let artwork_account = &mut ctx.accounts.artwork_account;
        
        // Validate royalty fee
        require!(new_royalty_fee <= program_state.max_secondary_royalty, ErrorCode::RoyaltyFeeTooHigh);
        
        // Update artwork royalty
        artwork_account.royalty_fee = new_royalty_fee;
        artwork_account.royalty_recipient = royalty_recipient;
        
        // Update metadata with new royalty
        let metadata_account = &ctx.accounts.metadata_account;
        let metadata_program = &ctx.accounts.metadata_program;
        let current_owner = &ctx.accounts.current_owner;
        
        invoke(
            &update_metadata_accounts_v2(
                metadata_program.key(),
                metadata_account.key(),
                current_owner.key(),
                None,
                Some(DataV2 {
                    name: artwork_account.name.clone(),
                    symbol: artwork_account.symbol.clone(),
                    uri: artwork_account.uri.clone(),
                    seller_fee_basis_points: new_royalty_fee,
                    creators: None,
                    collection: None,
                    uses: None,
                }),
                None,
                Some(false),
            ),
            &[
                metadata_account.to_account_info(),
                current_owner.to_account_info(),
            ],
        )?;

        msg!("Artwork royalty updated");
        msg!("Artwork ID: {}", artwork_account.artwork_id);
        msg!("New royalty fee: {}%", new_royalty_fee as f64 / 100.0);
        msg!("Royalty recipient: {}", royalty_recipient);
        
        Ok(())
    }

    /// Transfer artwork ownership
    pub fn transfer_artwork(ctx: Context<TransferArtwork>) -> Result<()> {
        let artwork_account = &mut ctx.accounts.artwork_account;
        let new_owner = &ctx.accounts.new_owner;
        
        // Update artwork owner
        artwork_account.current_owner = new_owner.key();
        
        // Transfer token
        let transfer_ctx = CpiContext::new(
            ctx.accounts.token_program.to_account_info(),
            Transfer {
                from: ctx.accounts.from_token_account.to_account_info(),
                to: ctx.accounts.to_token_account.to_account_info(),
                authority: ctx.accounts.current_owner.to_account_info(),
            },
        );
        
        token::transfer(transfer_ctx, 1)?;
        
        msg!("Artwork transferred");
        msg!("Artwork ID: {}", artwork_account.artwork_id);
        msg!("New owner: {}", new_owner.key());
        
        Ok(())
    }

    /// Update program settings (authority only)
    pub fn update_program_settings(
        ctx: Context<UpdateProgramSettings>,
        new_royalty_fee: Option<u16>,
        new_max_secondary_royalty: Option<u16>,
        is_paused: Option<bool>,
    ) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        
        if let Some(fee) = new_royalty_fee {
            require!(fee <= 1000, ErrorCode::RoyaltyFeeTooHigh); // Max 10% for primary
            program_state.royalty_fee = fee;
        }
        
        if let Some(max_fee) = new_max_secondary_royalty {
            require!(max_fee <= 1500, ErrorCode::RoyaltyFeeTooHigh); // Max 15% for secondary
            program_state.max_secondary_royalty = max_fee;
        }
        
        if let Some(paused) = is_paused {
            program_state.is_paused = paused;
        }
        
        msg!("Program settings updated");
        
        Ok(())
    }

    /// Get artwork info
    pub fn get_artwork_info(ctx: Context<GetArtworkInfo>) -> Result<()> {
        let artwork_account = &ctx.accounts.artwork_account;
        
        msg!("Artwork Info:");
        msg!("ID: {}", artwork_account.artwork_id);
        msg!("Mint: {}", artwork_account.mint);
        msg!("Creator: {}", artwork_account.creator);
        msg!("Current Owner: {}", artwork_account.current_owner);
        msg!("Name: {}", artwork_account.name);
        msg!("Royalty Fee: {}%", artwork_account.royalty_fee as f64 / 100.0);
        msg!("Royalty Recipient: {}", artwork_account.royalty_recipient);
        
        Ok(())
    }

    /// Register or update artist ranking
    pub fn register_artist_ranking(
        ctx: Context<RegisterArtistRanking>,
        total_artworks: u64,
        ranking_score: u64,
    ) -> Result<()> {
        let program_state = &ctx.accounts.program_state;
        let artist_ranking = &mut ctx.accounts.artist_ranking;
        
        // Only authority can register/update artist rankings
        require!(ctx.accounts.authority.key() == program_state.authority, ErrorCode::Unauthorized);
        
        artist_ranking.artist = ctx.accounts.artist.key();
        artist_ranking.total_artworks = total_artworks;
        artist_ranking.ranking_score = ranking_score;
        artist_ranking.last_updated = Clock::get()?.unix_timestamp;
        artist_ranking.bump = *ctx.bumps.get("artist_ranking").unwrap();
        
        // Calculate royalty ratio based on ranking score and total artworks
        let royalty_ratio = calculate_artist_royalty_ratio(ranking_score, total_artworks, program_state.total_artworks_published);
        artist_ranking.royalty_ratio = royalty_ratio;
        
        msg!("Artist ranking registered/updated");
        msg!("Artist: {}", artist_ranking.artist);
        msg!("Total artworks: {}", artist_ranking.total_artworks);
        msg!("Ranking score: {}", artist_ranking.ranking_score);
        msg!("Royalty ratio: {}%", royalty_ratio as f64 / 100.0);
        
        Ok(())
    }

    /// Create dynamic royalty distribution for masterpiece generation
    pub fn create_dynamic_royalty(
        ctx: Context<CreateDynamicRoyalty>,
        masterpiece_id: u64,
        participating_artists: Vec<Pubkey>,
    ) -> Result<()> {
        let program_state = &ctx.accounts.program_state;
        let dynamic_royalty = &mut ctx.accounts.dynamic_royalty;
        
        // Check if dynamic royalty is enabled
        require!(program_state.dynamic_royalty_enabled, ErrorCode::DynamicRoyaltyDisabled);
        
        // Validate participating artists (max 10)
        require!(participating_artists.len() <= 10, ErrorCode::TooManyArtists);
        require!(!participating_artists.is_empty(), ErrorCode::NoArtistsProvided);
        
        dynamic_royalty.masterpiece_id = masterpiece_id;
        dynamic_royalty.total_royalty_basis_points = 2000; // 20% total royalty
        dynamic_royalty.huraii_royalty_basis_points = 500; // 5% to HURAII system owner
        dynamic_royalty.artist_royalty_basis_points = 1500; // 15% to participating artists
        dynamic_royalty.participating_artists = participating_artists.clone();
        dynamic_royalty.created_at = Clock::get()?.unix_timestamp;
        dynamic_royalty.bump = *ctx.bumps.get("dynamic_royalty").unwrap();
        
        // Calculate individual artist shares based on their ranking ratios
        let artist_shares = calculate_artist_shares(&participating_artists, program_state)?;
        dynamic_royalty.artist_shares = artist_shares;
        
        msg!("Dynamic royalty distribution created");
        msg!("Masterpiece ID: {}", masterpiece_id);
        msg!("Total royalty: {}%", dynamic_royalty.total_royalty_basis_points as f64 / 100.0);
        msg!("HURAII system royalty: {}%", dynamic_royalty.huraii_royalty_basis_points as f64 / 100.0);
        msg!("Artist royalty: {}%", dynamic_royalty.artist_royalty_basis_points as f64 / 100.0);
        msg!("Participating artists: {}", participating_artists.len());
        
        Ok(())
    }

    /// Update total artworks published count
    pub fn update_total_artworks_published(
        ctx: Context<UpdateTotalArtworks>,
        new_total: u64,
    ) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        
        // Only authority can update total artworks
        require!(ctx.accounts.authority.key() == program_state.authority, ErrorCode::Unauthorized);
        
        program_state.total_artworks_published = new_total;
        
        msg!("Total artworks published updated");
        msg!("New total: {}", new_total);
        
        Ok(())
    }

    /// Set HURAII system owner
    pub fn set_huraii_system_owner(
        ctx: Context<SetHuraiiSystemOwner>,
        new_owner: Pubkey,
    ) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        
        // Only current authority can change HURAII system owner
        require!(ctx.accounts.authority.key() == program_state.authority, ErrorCode::Unauthorized);
        
        program_state.huraii_system_owner = new_owner;
        
        msg!("HURAII system owner updated");
        msg!("New owner: {}", new_owner);
        
        Ok(())
    }

    /// Toggle dynamic royalty feature
    pub fn toggle_dynamic_royalty(
        ctx: Context<ToggleDynamicRoyalty>,
    ) -> Result<()> {
        let program_state = &mut ctx.accounts.program_state;
        
        // Only authority can toggle dynamic royalty
        require!(ctx.accounts.authority.key() == program_state.authority, ErrorCode::Unauthorized);
        
        program_state.dynamic_royalty_enabled = !program_state.dynamic_royalty_enabled;
        
        msg!("Dynamic royalty toggled");
        msg!("Enabled: {}", program_state.dynamic_royalty_enabled);
        
        Ok(())
    }

    /// Get dynamic royalty distribution info
    pub fn get_dynamic_royalty_info(ctx: Context<GetDynamicRoyaltyInfo>) -> Result<()> {
        let dynamic_royalty = &ctx.accounts.dynamic_royalty;
        
        msg!("Dynamic Royalty Info:");
        msg!("Masterpiece ID: {}", dynamic_royalty.masterpiece_id);
        msg!("Total royalty: {}%", dynamic_royalty.total_royalty_basis_points as f64 / 100.0);
        msg!("HURAII system royalty: {}%", dynamic_royalty.huraii_royalty_basis_points as f64 / 100.0);
        msg!("Artist royalty: {}%", dynamic_royalty.artist_royalty_basis_points as f64 / 100.0);
        msg!("Participating artists: {}", dynamic_royalty.participating_artists.len());
        
        for (i, artist) in dynamic_royalty.participating_artists.iter().enumerate() {
            if i < dynamic_royalty.artist_shares.len() {
                msg!("Artist {}: {} ({}%)", i + 1, artist, dynamic_royalty.artist_shares[i] as f64 / 100.0);
            }
        }
        
        Ok(())
    }
}
}

#[derive(Accounts)]
#[instruction(bump: u8)]
pub struct Initialize<'info> {
    #[account(
        init,
        payer = authority,
        space = 8 + ProgramState::LEN,
        seeds = [b"program_state"],
        bump
    )]
    pub program_state: Account<'info, ProgramState>,
    
    #[account(mut)]
    pub authority: Signer<'info>,
    
    pub system_program: Program<'info, System>,
}

#[derive(Accounts)]
#[instruction(artwork_id: u64)]
pub struct MintArtwork<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump
    )]
    pub program_state: Account<'info, ProgramState>,
    
    #[account(
        init,
        payer = payer,
        space = 8 + ArtworkAccount::LEN,
        seeds = [b"artwork", artwork_id.to_le_bytes().as_ref()],
        bump
    )]
    pub artwork_account: Account<'info, ArtworkAccount>,
    
    /// CHECK: This is the mint account that will be created
    #[account(mut)]
    pub mint: AccountInfo<'info>,
    
    /// CHECK: This is the mint authority
    pub mint_authority: AccountInfo<'info>,
    
    /// CHECK: This is the associated token account
    #[account(mut)]
    pub associated_token_account: AccountInfo<'info>,
    
    /// CHECK: This is the metadata account
    #[account(mut)]
    pub metadata_account: AccountInfo<'info>,
    
    /// CHECK: This is the master edition account
    #[account(mut)]
    pub master_edition: AccountInfo<'info>,
    
    /// CHECK: This is the metadata program
    pub metadata_program: AccountInfo<'info>,
    
    #[account(mut)]
    pub recipient: Signer<'info>,
    
    #[account(mut)]
    pub payer: Signer<'info>,
    
    pub token_program: Program<'info, Token>,
    pub associated_token_program: Program<'info, AssociatedToken>,
    pub system_program: Program<'info, System>,
    pub rent: Sysvar<'info, Rent>,
}

#[derive(Accounts)]
pub struct SetArtworkRoyalty<'info> {
    #[account(
        seeds = [b"program_state"],
        bump = program_state.bump
    )]
    pub program_state: Account<'info, ProgramState>,
    
    #[account(
        mut,
        seeds = [b"artwork", artwork_account.artwork_id.to_le_bytes().as_ref()],
        bump = artwork_account.bump,
        has_one = current_owner
    )]
    pub artwork_account: Account<'info, ArtworkAccount>,
    
    /// CHECK: This is the metadata account
    #[account(mut)]
    pub metadata_account: AccountInfo<'info>,
    
    /// CHECK: This is the metadata program
    pub metadata_program: AccountInfo<'info>,
    
    pub current_owner: Signer<'info>,
}

#[derive(Accounts)]
pub struct TransferArtwork<'info> {
    #[account(
        mut,
        seeds = [b"artwork", artwork_account.artwork_id.to_le_bytes().as_ref()],
        bump = artwork_account.bump,
        has_one = current_owner
    )]
    pub artwork_account: Account<'info, ArtworkAccount>,
    
    #[account(mut)]
    pub from_token_account: Account<'info, TokenAccount>,
    
    #[account(mut)]
    pub to_token_account: Account<'info, TokenAccount>,
    
    pub current_owner: Signer<'info>,
    
    /// CHECK: This is the new owner
    pub new_owner: AccountInfo<'info>,
    
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct UpdateProgramSettings<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump,
        has_one = authority
    )]
    pub program_state: Account<'info, ProgramState>,
    
    pub authority: Signer<'info>,
}

#[derive(Accounts)]
pub struct GetArtworkInfo<'info> {
    #[account(
        seeds = [b"artwork", artwork_account.artwork_id.to_le_bytes().as_ref()],
        bump = artwork_account.bump
    )]
    pub artwork_account: Account<'info, ArtworkAccount>,
}

#[derive(Accounts)]
#[instruction(artist: Pubkey)]
pub struct RegisterArtistRanking<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump
    )]
    pub program_state: Account<'info, ProgramState>,
    
    #[account(
        init_if_needed,
        payer = authority,
        space = 8 + ArtistRanking::LEN,
        seeds = [b"artist_ranking", artist.key().as_ref()],
        bump
    )]
    pub artist_ranking: Account<'info, ArtistRanking>,
    
    /// CHECK: This is the artist being ranked
    pub artist: AccountInfo<'info>,
    
    pub authority: Signer<'info>,
    
    pub system_program: Program<'info, System>,
}

#[derive(Accounts)]
#[instruction(masterpiece_id: u64)]
pub struct CreateDynamicRoyalty<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump
    )]
    pub program_state: Account<'info, ProgramState>,
    
    #[account(
        init,
        payer = authority,
        space = 8 + DynamicRoyalty::LEN,
        seeds = [b"dynamic_royalty", masterpiece_id.to_le_bytes().as_ref()],
        bump
    )]
    pub dynamic_royalty: Account<'info, DynamicRoyalty>,
    
    pub authority: Signer<'info>,
    
    pub system_program: Program<'info, System>,
}

#[derive(Accounts)]
pub struct UpdateTotalArtworks<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump,
        has_one = authority
    )]
    pub program_state: Account<'info, ProgramState>,
    
    pub authority: Signer<'info>,
}

#[derive(Accounts)]
pub struct SetHuraiiSystemOwner<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump,
        has_one = authority
    )]
    pub program_state: Account<'info, ProgramState>,
    
    pub authority: Signer<'info>,
}

#[derive(Accounts)]
pub struct ToggleDynamicRoyalty<'info> {
    #[account(
        mut,
        seeds = [b"program_state"],
        bump = program_state.bump,
        has_one = authority
    )]
    pub program_state: Account<'info, ProgramState>,
    
    pub authority: Signer<'info>,
}

#[derive(Accounts)]
pub struct GetDynamicRoyaltyInfo<'info> {
    #[account(
        seeds = [b"dynamic_royalty", dynamic_royalty.masterpiece_id.to_le_bytes().as_ref()],
        bump = dynamic_royalty.bump
    )]
    pub dynamic_royalty: Account<'info, DynamicRoyalty>,
}

#[account]
pub struct ProgramState {
    pub authority: Pubkey,
    pub bump: u8,
    pub total_minted: u64,
    pub royalty_fee: u16,
    pub max_secondary_royalty: u16,
    pub is_paused: bool,
    pub huraii_system_owner: Pubkey, // Owner of the HURAII system
    pub total_artworks_published: u64, // Total artworks published on marketplace
    pub dynamic_royalty_enabled: bool, // Whether dynamic royalty distribution is enabled
}

impl ProgramState {
    pub const LEN: usize = 32 + 1 + 8 + 2 + 2 + 1 + 32 + 8 + 1; // Added huraii_system_owner, total_artworks_published, dynamic_royalty_enabled
}

#[account]
pub struct ArtworkAccount {
    pub artwork_id: u64,
    pub mint: Pubkey,
    pub creator: Pubkey,
    pub current_owner: Pubkey,
    pub name: String,
    pub symbol: String,
    pub uri: String,
    pub royalty_fee: u16,
    pub royalty_recipient: Pubkey,
    pub created_at: i64,
    pub bump: u8,
}

impl ArtworkAccount {
    pub const LEN: usize = 8 + 32 + 32 + 32 + 64 + 16 + 256 + 2 + 32 + 8 + 1;
}

/// Artist ranking and royalty distribution data
#[account]
pub struct ArtistRanking {
    pub artist: Pubkey, // Artist's public key
    pub total_artworks: u64, // Total artworks published by this artist
    pub ranking_score: u64, // Ranking score based on marketplace performance
    pub royalty_ratio: u16, // Royalty ratio in basis points (0-10000)
    pub last_updated: i64, // Last time ranking was updated
    pub bump: u8,
}

impl ArtistRanking {
    pub const LEN: usize = 32 + 8 + 8 + 2 + 8 + 1;
}

/// Dynamic royalty distribution for masterpiece generation
#[account]
pub struct DynamicRoyalty {
    pub masterpiece_id: u64, // ID of the masterpiece
    pub total_royalty_basis_points: u16, // Total royalty (20% = 2000 basis points)
    pub huraii_royalty_basis_points: u16, // HURAII system owner royalty (5% = 500 basis points)
    pub artist_royalty_basis_points: u16, // Remaining royalty for artists (15% = 1500 basis points)
    pub participating_artists: Vec<Pubkey>, // List of participating artists
    pub artist_shares: Vec<u16>, // Individual artist shares in basis points
    pub created_at: i64,
    pub bump: u8,
}

impl DynamicRoyalty {
    pub const LEN: usize = 8 + 2 + 2 + 2 + 4 + (32 * 10) + 4 + (2 * 10) + 8 + 1; // Max 10 artists
}

#[error_code]
pub enum ErrorCode {
    #[msg("Program is currently paused")]
    ProgramPaused,
    #[msg("Name is too long")]
    NameTooLong,
    #[msg("Symbol is too long")]
    SymbolTooLong,
    #[msg("URI is too long")]
    UriTooLong,
    #[msg("Royalty fee is too high")]
    RoyaltyFeeTooHigh,
    #[msg("Unauthorized")]
    Unauthorized,
    #[msg("Invalid artwork ID")]
    InvalidArtworkId,
    #[msg("Dynamic royalty feature is disabled")]
    DynamicRoyaltyDisabled,
    #[msg("Too many participating artists (max 10)")]
    TooManyArtists,
    #[msg("No participating artists provided")]
    NoArtistsProvided,
    #[msg("Invalid artist ranking data")]
    InvalidArtistRanking,
    #[msg("Artist ranking not found")]
    ArtistRankingNotFound,
} 
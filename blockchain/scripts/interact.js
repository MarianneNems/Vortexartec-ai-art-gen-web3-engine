const { ethers, network } = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("Interacting with VortexArtwork contract...");
  console.log("Network:", network.name);
  
  const [deployer] = await ethers.getSigners();
  console.log("Using account:", deployer.address);

  // Load deployment info
  const deploymentFile = path.join(__dirname, "../deployments", `${network.name}_deployment.json`);
  
  if (!fs.existsSync(deploymentFile)) {
    console.error("❌ Deployment file not found:", deploymentFile);
    console.error("Please deploy the contract first using: npx hardhat run scripts/deploy.js");
    process.exit(1);
  }

  const deploymentInfo = JSON.parse(fs.readFileSync(deploymentFile, 'utf8'));
  const contractAddress = deploymentInfo.contractAddress;
  
  console.log("Contract address:", contractAddress);

  // Get contract instance
  const VortexArtwork = await ethers.getContractFactory("VortexArtwork");
  const vortexArtwork = VortexArtwork.attach(contractAddress);

  // Interactive menu
  const readline = require('readline');
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });

  const askQuestion = (question) => {
    return new Promise((resolve) => {
      rl.question(question, (answer) => {
        resolve(answer);
      });
    });
  };

  console.log("\n=== VortexArtwork Contract Interaction ===");
  console.log("1. Mint NFT");
  console.log("2. Set token royalty");
  console.log("3. Get contract info");
  console.log("4. Get token info");
  console.log("5. Add minter");
  console.log("6. Remove minter");
  console.log("7. Exit");

  const choice = await askQuestion("\nSelect an option (1-7): ");

  try {
    switch (choice) {
      case "1":
        await mintNFT(vortexArtwork, askQuestion);
        break;
      case "2":
        await setTokenRoyalty(vortexArtwork, askQuestion);
        break;
      case "3":
        await getContractInfo(vortexArtwork);
        break;
      case "4":
        await getTokenInfo(vortexArtwork, askQuestion);
        break;
      case "5":
        await addMinter(vortexArtwork, askQuestion);
        break;
      case "6":
        await removeMinter(vortexArtwork, askQuestion);
        break;
      case "7":
        console.log("Goodbye!");
        break;
      default:
        console.log("Invalid option");
    }
  } catch (error) {
    console.error("❌ Error:", error.message);
  }

  rl.close();
}

async function mintNFT(contract, askQuestion) {
  console.log("\n=== Mint NFT ===");
  
  const toAddress = await askQuestion("Enter recipient address: ");
  const tokenURI = await askQuestion("Enter token URI: ");
  
  console.log("Minting NFT...");
  const tx = await contract.mintArtwork(toAddress, tokenURI);
  console.log("Transaction hash:", tx.hash);
  
  const receipt = await tx.wait();
  console.log("✅ NFT minted successfully!");
  console.log("Gas used:", receipt.gasUsed.toString());
  
  // Get the minted token ID from events
  const event = receipt.events.find(e => e.event === 'ArtworkMinted');
  if (event) {
    console.log("Token ID:", event.args.tokenId.toString());
  }
}

async function setTokenRoyalty(contract, askQuestion) {
  console.log("\n=== Set Token Royalty ===");
  
  const tokenId = await askQuestion("Enter token ID: ");
  const receiver = await askQuestion("Enter royalty receiver address: ");
  const feePercent = await askQuestion("Enter royalty fee percentage (e.g., 10 for 10%): ");
  
  const feeNumerator = Math.floor(parseFloat(feePercent) * 100); // Convert to basis points
  
  console.log("Setting token royalty...");
  const tx = await contract.setTokenRoyalty(tokenId, receiver, feeNumerator);
  console.log("Transaction hash:", tx.hash);
  
  const receipt = await tx.wait();
  console.log("✅ Token royalty set successfully!");
  console.log("Gas used:", receipt.gasUsed.toString());
}

async function getContractInfo(contract) {
  console.log("\n=== Contract Info ===");
  
  const name = await contract.name();
  const symbol = await contract.symbol();
  const currentTokenId = await contract.getCurrentTokenId();
  const owner = await contract.owner();
  
  console.log("Name:", name);
  console.log("Symbol:", symbol);
  console.log("Current Token ID:", currentTokenId.toString());
  console.log("Owner:", owner);
  
  // Get default royalty info
  const [royaltyReceiver, royaltyAmount] = await contract.royaltyInfo(0, ethers.utils.parseEther("1"));
  console.log("Default royalty receiver:", royaltyReceiver);
  console.log("Default royalty amount (1 ETH sale):", ethers.utils.formatEther(royaltyAmount), "ETH");
}

async function getTokenInfo(contract, askQuestion) {
  console.log("\n=== Token Info ===");
  
  const tokenId = await askQuestion("Enter token ID: ");
  
  try {
    const owner = await contract.ownerOf(tokenId);
    const tokenURI = await contract.tokenURI(tokenId);
    const creator = await contract.getArtworkCreator(tokenId);
    
    console.log("Token ID:", tokenId);
    console.log("Owner:", owner);
    console.log("Creator:", creator);
    console.log("Token URI:", tokenURI);
    
    // Get royalty info
    const [royaltyReceiver, royaltyAmount] = await contract.royaltyInfo(tokenId, ethers.utils.parseEther("1"));
    console.log("Royalty receiver:", royaltyReceiver);
    console.log("Royalty amount (1 ETH sale):", ethers.utils.formatEther(royaltyAmount), "ETH");
    
  } catch (error) {
    console.log("❌ Token not found or error:", error.message);
  }
}

async function addMinter(contract, askQuestion) {
  console.log("\n=== Add Minter ===");
  
  const minterAddress = await askQuestion("Enter minter address: ");
  
  console.log("Adding minter...");
  const tx = await contract.addMinter(minterAddress);
  console.log("Transaction hash:", tx.hash);
  
  const receipt = await tx.wait();
  console.log("✅ Minter added successfully!");
  console.log("Gas used:", receipt.gasUsed.toString());
}

async function removeMinter(contract, askQuestion) {
  console.log("\n=== Remove Minter ===");
  
  const minterAddress = await askQuestion("Enter minter address: ");
  
  console.log("Removing minter...");
  const tx = await contract.removeMinter(minterAddress);
  console.log("Transaction hash:", tx.hash);
  
  const receipt = await tx.wait();
  console.log("✅ Minter removed successfully!");
  console.log("Gas used:", receipt.gasUsed.toString());
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  }); 
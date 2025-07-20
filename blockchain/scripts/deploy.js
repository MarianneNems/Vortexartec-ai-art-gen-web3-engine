const { ethers, network } = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("Deploying VortexArtwork contract...");
  console.log("Network:", network.name);
  
  const [deployer] = await ethers.getSigners();
  console.log("Deploying contracts with account:", deployer.address);
  console.log("Account balance:", (await deployer.getBalance()).toString());

  // Contract deployment parameters
  const COLLECTION_NAME = process.env.COLLECTION_NAME || "VORTEX AI Artworks";
  const COLLECTION_SYMBOL = process.env.COLLECTION_SYMBOL || "VORTEX";
  const ROYALTY_RECEIVER = process.env.ROYALTY_RECEIVER || deployer.address;
  const ROYALTY_FEE = process.env.ROYALTY_FEE || 500; // 5% = 500 basis points

  console.log("Deployment parameters:");
  console.log("- Collection Name:", COLLECTION_NAME);
  console.log("- Collection Symbol:", COLLECTION_SYMBOL);
  console.log("- Royalty Receiver:", ROYALTY_RECEIVER);
  console.log("- Royalty Fee:", ROYALTY_FEE, "basis points");

  // Get the contract factory
  const VortexArtwork = await ethers.getContractFactory("VortexArtwork");
  
  // Deploy the contract
  const vortexArtwork = await VortexArtwork.deploy(
    COLLECTION_NAME,
    COLLECTION_SYMBOL,
    ROYALTY_RECEIVER,
    ROYALTY_FEE
  );

  await vortexArtwork.deployed();

  console.log("\n✅ VortexArtwork deployed successfully!");
  console.log("Contract address:", vortexArtwork.address);
  console.log("Transaction hash:", vortexArtwork.deployTransaction.hash);

  // Save deployment info
  const deploymentInfo = {
    network: network.name,
    contractAddress: vortexArtwork.address,
    contractName: "VortexArtwork",
    deployerAddress: deployer.address,
    transactionHash: vortexArtwork.deployTransaction.hash,
    blockNumber: vortexArtwork.deployTransaction.blockNumber,
    deploymentTime: new Date().toISOString(),
    constructorArgs: {
      name: COLLECTION_NAME,
      symbol: COLLECTION_SYMBOL,
      royaltyReceiver: ROYALTY_RECEIVER,
      royaltyFee: ROYALTY_FEE
    }
  };

  // Create deployments directory if it doesn't exist
  const deploymentsDir = path.join(__dirname, "../deployments");
  if (!fs.existsSync(deploymentsDir)) {
    fs.mkdirSync(deploymentsDir, { recursive: true });
  }

  // Save deployment info to file
  const deploymentFile = path.join(deploymentsDir, `${network.name}_deployment.json`);
  fs.writeFileSync(deploymentFile, JSON.stringify(deploymentInfo, null, 2));
  
  console.log("Deployment info saved to:", deploymentFile);

  // Wait for a few block confirmations before verification
  if (network.name !== "hardhat" && network.name !== "localhost") {
    console.log("\nWaiting for block confirmations...");
    await vortexArtwork.deployTransaction.wait(6);
    
    // Verify contract on Etherscan/Polygonscan
    if (process.env.VERIFY_CONTRACTS === "true") {
      console.log("\nVerifying contract on blockchain explorer...");
      try {
        await hre.run("verify:verify", {
          address: vortexArtwork.address,
          constructorArguments: [
            COLLECTION_NAME,
            COLLECTION_SYMBOL,
            ROYALTY_RECEIVER,
            ROYALTY_FEE
          ]
        });
        console.log("✅ Contract verified successfully!");
      } catch (error) {
        console.log("❌ Contract verification failed:", error.message);
      }
    }
  }

  // Test basic functionality
  console.log("\nTesting basic contract functionality...");
  
  try {
    const name = await vortexArtwork.name();
    const symbol = await vortexArtwork.symbol();
    const currentTokenId = await vortexArtwork.getCurrentTokenId();
    const isMinter = await vortexArtwork.minters(deployer.address);
    
    console.log("- Name:", name);
    console.log("- Symbol:", symbol);
    console.log("- Current Token ID:", currentTokenId.toString());
    console.log("- Deployer is minter:", isMinter);
    
    // Test royalty info
    const [royaltyReceiver, royaltyAmount] = await vortexArtwork.royaltyInfo(0, ethers.utils.parseEther("1"));
    console.log("- Royalty receiver:", royaltyReceiver);
    console.log("- Royalty amount (1 ETH sale):", ethers.utils.formatEther(royaltyAmount), "ETH");
    
  } catch (error) {
    console.log("❌ Contract testing failed:", error.message);
  }

  console.log("\n🎉 Deployment completed successfully!");
  console.log("Contract address:", vortexArtwork.address);
  console.log("Remember to update your WordPress plugin with this contract address.");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  }); 
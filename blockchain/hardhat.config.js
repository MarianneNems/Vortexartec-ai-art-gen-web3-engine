require("@nomicfoundation/hardhat-toolbox");
require("@nomicfoundation/hardhat-verify");
require("@openzeppelin/hardhat-upgrades");
require("dotenv").config();

// Secure environment variable handling
const PRIVATE_KEY = process.env.PRIVATE_KEY;
const INFURA_API_KEY = process.env.INFURA_API_KEY;
const ETHERSCAN_API_KEY = process.env.ETHERSCAN_API_KEY;
const POLYGONSCAN_API_KEY = process.env.POLYGONSCAN_API_KEY;

// Validate critical environment variables
if (!PRIVATE_KEY && process.env.NODE_ENV !== 'development') {
    console.error('ERROR: PRIVATE_KEY environment variable is required for production deployments');
    process.exit(1);
}

if (!INFURA_API_KEY && process.env.NODE_ENV !== 'development') {
    console.error('ERROR: INFURA_API_KEY environment variable is required for network connections');
    process.exit(1);
}

// Use secure fallback for development only
const DEVELOPMENT_PRIVATE_KEY = "0x0000000000000000000000000000000000000000000000000000000000000001";
const SAFE_PRIVATE_KEY = PRIVATE_KEY || (process.env.NODE_ENV === 'development' ? DEVELOPMENT_PRIVATE_KEY : null);

if (!SAFE_PRIVATE_KEY) {
    console.error('ERROR: No valid private key available');
    process.exit(1);
}

/** @type import('hardhat/config').HardhatUserConfig */
module.exports = {
  solidity: {
    version: "0.8.19",
    settings: {
      optimizer: {
        enabled: true,
        runs: 200
      }
    }
  },
  
  networks: {
    hardhat: {
      chainId: 1337
    },
    
    ...(INFURA_API_KEY && SAFE_PRIVATE_KEY ? {
      mainnet: {
        url: `https://mainnet.infura.io/v3/${INFURA_API_KEY}`,
        accounts: [SAFE_PRIVATE_KEY],
        gasPrice: 20000000000, // 20 gwei
        gas: 6000000,
        timeout: 60000
      },
      
      sepolia: {
        url: `https://sepolia.infura.io/v3/${INFURA_API_KEY}`,
        accounts: [SAFE_PRIVATE_KEY],
        gasPrice: 20000000000,
        gas: 6000000,
        timeout: 60000
      },
      
      polygon: {
        url: `https://polygon-mainnet.infura.io/v3/${INFURA_API_KEY}`,
        accounts: [SAFE_PRIVATE_KEY],
        gasPrice: 30000000000, // 30 gwei
        gas: 6000000,
        timeout: 60000
      },
      
      mumbai: {
        url: `https://polygon-mumbai.infura.io/v3/${INFURA_API_KEY}`,
        accounts: [SAFE_PRIVATE_KEY],
        gasPrice: 20000000000,
        gas: 6000000,
        timeout: 60000
      }
    } : {})
  },
  
  etherscan: {
    apiKey: {
      ...(ETHERSCAN_API_KEY ? {
        mainnet: ETHERSCAN_API_KEY,
        sepolia: ETHERSCAN_API_KEY
      } : {}),
      ...(POLYGONSCAN_API_KEY ? {
        polygon: POLYGONSCAN_API_KEY,
        polygonMumbai: POLYGONSCAN_API_KEY
      } : {})
    }
  },
  
  gasReporter: {
    enabled: process.env.REPORT_GAS !== undefined,
    currency: "USD"
  }
}; 
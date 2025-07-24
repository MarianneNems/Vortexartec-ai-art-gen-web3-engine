# 🚀 VORTEX AI Engine - Solana Blockchain Integration Guide

## Overview

The VORTEX AI Engine now includes comprehensive Solana blockchain integration with devnet testing, metrics collection, and smart contract deployment capabilities. This integration enables artists and collectors to interact with the Solana blockchain directly through the WordPress interface.

## 🌟 Features

### ✅ **Complete Solana Integration**
- **Devnet/Testnet/Mainnet Support** - Full network configuration
- **Real-time Metrics Collection** - Blockchain performance monitoring
- **Smart Contract Deployment** - Program deployment and management
- **Transaction Management** - Send/receive SOL and tokens
- **Validator Integration** - Run your own Solana validator
- **Health Monitoring** - Network status and connection testing

### ✅ **Metrics & Analytics**
- **InfluxDB Integration** - Professional metrics storage
- **Real-time Dashboard** - Live blockchain data visualization
- **Performance Tracking** - TPS, block time, confirmation metrics
- **Network Statistics** - Validator count, supply, cluster nodes

### ✅ **Developer Tools**
- **CLI Integration** - Solana command-line tools
- **Program Deployment** - Smart contract deployment interface
- **Keypair Management** - Secure wallet management
- **Network Configuration** - Easy network switching

## 🛠️ Installation & Setup

### Prerequisites

1. **Solana CLI Tools**
   ```bash
   # Install Solana CLI
   sh -c "$(curl -sSfL https://release.solana.com/v1.17.0/install)"
   ```

2. **PHP Extensions**
   - `curl` - For API requests
   - `json` - For data processing
   - `openssl` - For cryptographic operations

3. **WordPress Requirements**
   - WordPress 5.0+
   - PHP 7.4+
   - MySQL 5.7+

### Step 1: Plugin Installation

1. **Upload the plugin** to your WordPress site
2. **Activate** the VORTEX AI Engine plugin
3. **Navigate** to VORTEX AI Engine → Solana in admin

### Step 2: Solana Configuration

#### Automatic Setup (Recommended)
```powershell
# Run the setup script
.\deployment\setup-solana-devnet.ps1 -Network devnet -SetupValidator
```

#### Manual Configuration

1. **Set Environment Variables**
   ```bash
   export SOLANA_METRICS_CONFIG="host=https://metrics.solana.com:8086,db=devnet,u=scratch_writer,p=topsecret"
   export SOLANA_NETWORK="devnet"
   export SOLANA_RPC_URL="https://api.devnet.solana.com"
   ```

2. **Configure Solana CLI**
   ```bash
   solana config set --url https://api.devnet.solana.com
   solana config set --commitment confirmed
   solana config set --expected-genesis-hash EtWTRABZaYq6iMfeYKouRu166VU2xqa1wcaWoxPkrZBG
   ```

3. **Generate Keypairs**
   ```bash
   solana-keygen new --outfile validator-keypair.json
   solana-keygen new --outfile vote-account-keypair.json
   ```

### Step 3: WordPress Configuration

1. **Navigate** to VORTEX AI Engine → Solana Dashboard
2. **Configure** network settings:
   - RPC URL: `https://api.devnet.solana.com`
   - Metrics Host: `https://metrics.solana.com:8086`
   - Database: `devnet`
   - Username: `scratch_writer`
   - Password: `topsecret`

3. **Test Connection** using the dashboard interface

## 📊 Dashboard Features

### Real-time Metrics
- **Current Slot** - Latest blockchain slot
- **Block Height** - Current block number
- **Transaction Count** - Total transactions
- **Validator Count** - Active validators
- **Supply** - Total SOL supply
- **Cluster Nodes** - Network nodes

### Performance Indicators
- **TPS (Transactions/sec)** - Network throughput
- **Block Time** - Average block time in milliseconds
- **Confirmation Time** - Transaction confirmation speed

### Program Management
- **Deploy Programs** - Upload and deploy smart contracts
- **Program Status** - Monitor deployed programs
- **Transaction History** - View all transactions
- **Account Balances** - Check SOL and token balances

## 🔧 Configuration Options

### Network Configuration

#### Devnet (Testing)
```php
$solana_config = [
    'rpc_url' => 'https://api.devnet.solana.com',
    'ws_url' => 'wss://api.devnet.solana.com',
    'genesis_hash' => 'EtWTRABZaYq6iMfeYKouRu166VU2xqa1wcaWoxPkrZBG',
    'entrypoints' => [
        'entrypoint.devnet.solana.com:8001',
        'entrypoint2.devnet.solana.com:8001',
        'entrypoint3.devnet.solana.com:8001',
        'entrypoint4.devnet.solana.com:8001',
        'entrypoint5.devnet.solana.com:8001'
    ]
];
```

#### Testnet
```php
$solana_config = [
    'rpc_url' => 'https://api.testnet.solana.com',
    'ws_url' => 'wss://api.testnet.solana.com',
    'genesis_hash' => '4uhcVNiUZhFDVUTM1vp3AjT1xq7X7zpnjZdf6JRCuCjz'
];
```

#### Mainnet Beta
```php
$solana_config = [
    'rpc_url' => 'https://api.mainnet-beta.solana.com',
    'ws_url' => 'wss://api.mainnet-beta.solana.com',
    'genesis_hash' => '5eykt4UsFv8P8NJdTREpY1vzqKqZKvdpKuc147dw2N9d'
];
```

### Metrics Configuration
```php
$metrics_config = [
    'host' => 'https://metrics.solana.com:8086',
    'database' => 'devnet',
    'username' => 'scratch_writer',
    'password' => 'topsecret',
    'retention_policy' => '30d',
    'measurement_prefix' => 'vortex_solana_'
];
```

### Validator Configuration
```bash
agave-validator \
    --identity validator-keypair.json \
    --vote-account vote-account-keypair.json \
    --known-validator dv1ZAGvdsz5hHLwWXsVnM94hWf1pjbKVau1QVkaMJ92 \
    --known-validator dv2eQHeP4RFrJZ6UeiZWoc3XTtmtZCUKxxCApCDcRNV \
    --known-validator dv4ACNkpYPcE3aKmYDqZm9G5EB3J4MRoeE7WNDRBVJB \
    --known-validator dv3qDFk1DTF36Z62bNvrCXe9sKATA6xvVy6A798xxAS \
    --only-known-rpc \
    --ledger ledger \
    --rpc-port 8899 \
    --dynamic-port-range 8000-8020 \
    --entrypoint entrypoint.devnet.solana.com:8001 \
    --entrypoint entrypoint2.devnet.solana.com:8001 \
    --entrypoint entrypoint3.devnet.solana.com:8001 \
    --entrypoint entrypoint4.devnet.solana.com:8001 \
    --entrypoint entrypoint5.devnet.solana.com:8001 \
    --expected-genesis-hash EtWTRABZaYq6iMfeYKouRu166VU2xqa1wcaWoxPkrZBG \
    --wal-recovery-mode skip_any_corrupted_record \
    --limit-ledger-size
```

## 🎯 Usage Examples

### Get Account Balance
```php
$solana = new Vortex_Solana_Integration();
$balance = $solana->get_balance('YOUR_PUBLIC_KEY', 'devnet');
echo "Balance: " . $balance['balance'] . " SOL";
```

### Send Transaction
```php
$result = $solana->send_transaction(
    $from_keypair,
    'RECIPIENT_PUBLIC_KEY',
    1.5, // SOL amount
    'devnet'
);
echo "Transaction: " . $result['signature'];
```

### Deploy Program
```php
$result = $solana->deploy_program(
    '/path/to/your/program',
    'devnet'
);
echo "Program ID: " . $result['program_id'];
```

### Collect Metrics
```php
$solana->collect_metrics();
```

### Test Connection
```php
$health = $solana->health_check();
var_dump($health);
```

## 📈 API Endpoints

### WordPress REST API

#### Get Solana Status
```
GET /wp-json/vortex/v1/solana/status
```

#### Get Metrics
```
GET /wp-json/vortex/v1/solana/metrics
```

#### Get Programs
```
GET /wp-json/vortex/v1/solana/programs
```

#### Deploy Program
```
POST /wp-json/vortex/v1/solana/deploy
{
    "program_path": "/path/to/program",
    "network": "devnet"
}
```

#### Send Transaction
```
POST /wp-json/vortex/v1/solana/transaction
{
    "from_keypair": "...",
    "to_public_key": "...",
    "amount": 1.5,
    "network": "devnet"
}
```

## 🔍 Monitoring & Debugging

### Health Checks
The system automatically performs health checks every hour:
- RPC connection status
- Metrics collection status
- Validator connection status

### Log Files
Check WordPress debug log for Solana-related messages:
```php
error_log('VORTEX AI Engine: Solana metrics collected');
error_log('VORTEX AI Engine: Solana health check completed');
```

### Database Tables
The integration creates several database tables:
- `wp_vortex_solana_metrics` - Metrics data
- `wp_vortex_solana_programs` - Deployed programs
- `wp_vortex_solana_health` - Health check results
- `wp_vortex_solana_transactions` - Transaction history
- `wp_vortex_solana_accounts` - Account data

### Metrics Dashboard
Access the metrics dashboard at:
```
https://metrics.solana.com:8086
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Connection Failed
**Problem**: Cannot connect to Solana RPC
**Solution**: 
- Check network connectivity
- Verify RPC URL is correct
- Ensure firewall allows connections

#### 2. Metrics Not Collecting
**Problem**: InfluxDB metrics not being sent
**Solution**:
- Verify InfluxDB credentials
- Check network connectivity to metrics host
- Ensure database exists

#### 3. Program Deployment Fails
**Problem**: Smart contract deployment fails
**Solution**:
- Check program compilation
- Verify sufficient SOL balance
- Ensure program path is correct

#### 4. Validator Not Starting
**Problem**: Agave validator fails to start
**Solution**:
- Check keypair files exist
- Verify port availability
- Ensure sufficient disk space

### Debug Commands

#### Test RPC Connection
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"getHealth"}' \
  https://api.devnet.solana.com
```

#### Check Solana CLI
```bash
solana --version
solana config get
solana cluster-version
```

#### Test Metrics Connection
```bash
curl -I https://metrics.solana.com:8086/ping
```

## 🔐 Security Considerations

### Key Management
- Store keypairs securely
- Use environment variables for sensitive data
- Regularly rotate keys
- Never commit keys to version control

### Network Security
- Use HTTPS for all connections
- Implement rate limiting
- Monitor for suspicious activity
- Keep software updated

### Access Control
- Restrict admin access to Solana dashboard
- Use WordPress user roles
- Implement audit logging
- Regular security audits

## 📚 Additional Resources

### Documentation
- [Solana Documentation](https://docs.solana.com/)
- [Solana Devnet Guide](https://docs.solana.com/developing/clients/devnet)
- [InfluxDB Documentation](https://docs.influxdata.com/)

### Tools
- [Solana Explorer](https://explorer.solana.com/)
- [Solana CLI](https://docs.solana.com/cli)
- [Agave Validator](https://github.com/agave-labs/agave)

### Community
- [Solana Discord](https://discord.gg/solana)
- [Solana Forum](https://forums.solana.com/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/solana)

## 🎉 Getting Started

1. **Install** the VORTEX AI Engine plugin
2. **Run** the Solana setup script
3. **Configure** your network settings
4. **Test** the connection
5. **Deploy** your first program
6. **Monitor** metrics and performance

## 📞 Support

For technical support:
- **GitHub Issues**: [Vortex AI Engine Repository](https://github.com/MarianneNems/vortex-artec-ai-marketplace)
- **Email**: support@vortexartec.com
- **Documentation**: [Vortex AI Engine Docs](https://vortexartec.com/docs)

---

**Happy coding on Solana! 🚀** 
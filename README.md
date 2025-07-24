<<<<<<< HEAD
# 🚀 VORTEX AI Engine - WordPress Plugin

> **Advanced AI-Powered Art Marketplace & Creative Platform**

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/plugins/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2+-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🌟 Overview

VORTEX AI Engine is a revolutionary WordPress plugin that transforms your website into an AI-powered art marketplace and creative platform. It integrates advanced AI agents, blockchain technology, and machine learning to create a comprehensive ecosystem for artists and art enthusiasts.

## ✨ Key Features

### 🤖 AI Agents & Orchestration
- **ARCHER** - Master AI Orchestrator with continuous learning
- **HURAII** - GPU-powered image generation (Stable Diffusion)
- **CLOE** - Market analysis and trend prediction
- **HORACE** - Content optimization and enhancement
- **THORIUS** - Platform guidance and user assistance

### 🎨 Artist Journey System
- **Registration & Onboarding** - Multi-step artist signup
- **Role & Expertise Quiz** - Personalized artist profiling
- **Horas Business Quiz** - Business acumen assessment
- **Reinforcement Learning** - Continuous improvement loops
- **Achievement System** - Milestone tracking and rewards

### 💎 TOLA Art Ecosystem
- **Daily Art Generation** - Automated "Art of the Day" creation
- **Smart Contract Integration** - Blockchain-based royalties
- **NFT Minting** - Tokenized artwork creation
- **Marketplace** - Complete buying/selling platform

### 🔗 Blockchain Integration
- **Solana Wallet** - Secure cryptocurrency integration
- **TOLA Tokens** - Custom token minting and transfer
- **Smart Contracts** - Automated royalty distribution
- **NFT Gallery** - Tokenized artwork display

### ☁️ Cloud Services
- **AWS S3** - Scalable file storage
- **RunPod** - GPU-powered AI processing
- **Gradio** - AI model deployment
- **DynamoDB** - Real-time data storage

## 🛠️ Installation

### Prerequisites
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- SSL certificate (for production)

### Quick Install
1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/vortex-ai-engine/`
3. **Activate** the plugin in WordPress admin
4. **Configure** AI endpoints and blockchain settings
5. **Test** the system with sample data

### Advanced Setup
```bash
# Clone the repository
git clone https://github.com/your-username/vortex-ai-engine.git

# Navigate to plugin directory
cd vortex-ai-engine

# Install dependencies (if any)
composer install

# Activate in WordPress
wp plugin activate vortex-ai-engine
```

## 📋 Configuration

### AI Endpoints
```php
// Configure AI service endpoints
define('VORTEX_HURAII_SD_ENDPOINT', 'https://your-sd-endpoint.com');
define('VORTEX_RUNPOD_ENDPOINT', 'https://your-runpod-endpoint.com');
define('VORTEX_GRADIO_ENDPOINT', 'https://your-gradio-endpoint.com');
```

### Blockchain Settings
```php
// Solana network configuration
define('VORTEX_SOLANA_NETWORK', 'mainnet-beta');
define('VORTEX_TOLA_TOKEN_ADDRESS', 'your-token-address');
```

### Cloud Services
```php
// AWS S3 configuration
define('VORTEX_S3_BUCKET', 'your-artwork-bucket');
define('VORTEX_S3_REGION', 'us-east-1');
```

## 🎯 Usage

### Shortcodes

#### Artist Journey
```php
[vortex_signup]           // Artist registration form
[vortex_connect_wallet]   // Wallet connection interface
[vortex_artist_quiz]      // Role & expertise assessment
[vortex_horas_quiz]       // Business acumen quiz
[vortex_artist_dashboard] // Artist dashboard
```

#### AI Generation
```php
[huraii_generate]         // AI image generation
[huraii_voice]           // Voice interaction
```

#### Marketplace
```php
[vortex_marketplace]      // Main marketplace
[vortex_artwork_gallery]  // Artwork display
[vortex_auction_house]    // Auction system
[vortex_shopping_cart]    // Shopping cart
```

#### Blockchain
```php
[vortex_wallet]          // Wallet management
[vortex_swap]            // Token swapping
[vortex_metrics]         // Performance metrics
[tola_nft_gallery]       // NFT gallery
```

### API Endpoints

#### AJAX Actions
```php
// Image generation
wp_ajax_vortex_generate_image

// Connection testing
wp_ajax_vortex_test_connection

// Activity monitoring
wp_ajax_vortex_get_activity

// Journey statistics
wp_ajax_vortex_get_journey_stats
```

## 🏗️ Architecture

### Directory Structure
```
vortex-ai-engine/
├── admin/                    # Admin interface
│   ├── css/                 # Admin styles
│   ├── js/                  # Admin scripts
│   └── *.php               # Admin pages
├── includes/                # Core functionality
│   ├── ai-agents/          # AI agent classes
│   ├── artist-journey/     # Artist journey system
│   ├── blockchain/         # Blockchain integration
│   ├── cloud/              # Cloud services
│   ├── database/           # Database management
│   ├── secret-sauce/       # Proprietary algorithms
│   ├── storage/            # Storage management
│   ├── subscriptions/      # Subscription system
│   └── tola-art/           # TOLA Art automation
├── public/                  # Frontend interface
├── contracts/               # Smart contracts
├── audit-system/            # Self-improvement system
└── vortex-ai-engine.php     # Main plugin file
```

### Core Classes
- `VORTEX_ARCHER_Orchestrator` - Master AI coordination
- `Vortex_Artist_Journey` - Artist lifecycle management
- `Vortex_Tola_Token_Handler` - Blockchain operations
- `Vortex_Activity_Logger` - Real-time activity tracking
- `Vortex_Database_Manager` - Database operations

## 🔧 Development

### Local Development
```bash
# Set up local environment
wp-env start

# Run tests
composer test

# Build assets
npm run build
```

### Code Standards
- **PHP**: PSR-12 coding standards
- **JavaScript**: ESLint configuration
- **CSS**: Stylelint rules
- **Documentation**: PHPDoc comments

### Testing
```bash
# Run PHP unit tests
composer test

# Run integration tests
composer test:integration

# Run performance tests
composer test:performance
```

## 📊 Performance

### Optimization Features
- **Redis Caching** - High-performance caching
- **Database Indexing** - Optimized queries
- **Asset Minification** - Compressed resources
- **CDN Integration** - Global content delivery
- **Lazy Loading** - On-demand resource loading

### Monitoring
- **Real-time Activity Logging** - Comprehensive tracking
- **Performance Metrics** - System health monitoring
- **Error Tracking** - Automated error reporting
- **Usage Analytics** - User behavior insights

## 🔒 Security

### Security Features
- **ABSPATH Guards** - Direct access prevention
- **Nonce Verification** - CSRF protection
- **Input Sanitization** - XSS prevention
- **SQL Injection Protection** - Database security
- **Role-based Access** - Permission management

### Best Practices
- Regular security audits
- Dependency updates
- Vulnerability scanning
- Penetration testing

## 📈 Roadmap

### Version 3.1.0 (Q1 2025)
- [ ] Enhanced AI model integration
- [ ] Advanced analytics dashboard
- [ ] Mobile app development
- [ ] Multi-language support

### Version 3.2.0 (Q2 2025)
- [ ] Advanced NFT features
- [ ] Social media integration
- [ ] AI-powered recommendations
- [ ] Advanced marketplace features

### Version 4.0.0 (Q3 2025)
- [ ] Decentralized marketplace
- [ ] Advanced AI agents
- [ ] Cross-chain integration
- [ ] Enterprise features

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

### Code Review Process
- All changes require review
- Automated testing must pass
- Documentation updates required
- Security review for sensitive changes

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [User Guide](docs/user-guide.md)
- [Developer Guide](docs/developer-guide.md)
- [API Reference](docs/api-reference.md)
- [Troubleshooting](docs/troubleshooting.md)

### Community
- [Discord Server](https://discord.gg/vortex-ai)
- [GitHub Discussions](https://github.com/your-username/vortex-ai-engine/discussions)
- [WordPress Support Forum](https://wordpress.org/support/plugin/vortex-ai-engine/)

### Professional Support
- [Enterprise Support](https://vortex-ai.com/support)
- [Custom Development](https://vortex-ai.com/services)
- [Training & Consulting](https://vortex-ai.com/training)

## 🙏 Acknowledgments

- **WordPress Community** - For the amazing platform
- **AI Research Community** - For cutting-edge AI models
- **Blockchain Developers** - For decentralized technology
- **Open Source Contributors** - For their valuable contributions

## 📞 Contact

- **Website**: https://vortex-ai.com
- **Email**: support@vortex-ai.com
- **Twitter**: [@VortexAI](https://twitter.com/VortexAI)
- **LinkedIn**: [Vortex AI](https://linkedin.com/company/vortex-ai)

---

**Made with ❤️ by the VORTEX AI Team**

*Transforming the future of digital art and creativity* 
=======
﻿# VORTEX AI Marketplace

A blockchain-powered art marketplace with integrated AI agent orchestration, TOLA token functionality, and multi-agent AI art generation tools.

## Overview

VORTEX is a comprehensive AI-powered marketplace for digital art, featuring:

- **Multi-Agent AI System**: THORIUS, HURAII, CLOE, and Business Strategist agents
- **Blockchain Integration**: Solana-based TOLA token for transactions and rewards
- **Advanced Orchestration**: Intelligent query routing and collaborative responses
- **Artist Tools**: AI-assisted art generation and portfolio management
- **Collector Features**: Personalized recommendations and investment tracking

## AI Agents

### THORIUS (Ethical Concierge)

THORIUS serves as the central orchestrator for the VORTEX ecosystem, coordinating between specialized AI agents to provide optimal responses. It features:

- **Intelligent Query Routing**: Determines which agent can best handle a specific query
- **Collaborative Processing**: Combines insights from multiple agents for complex queries
- **Domain-Specific Refinement**: Tailors queries for each agent's expertise
- **Content Blending**: Sophisticated algorithms for merging responses from different agents
- **Security Governance**: Ensures ethical AI usage and user data protection

### HURAII (Artistic AI)

HURAII is the creative engine of VORTEX, specializing in art generation and style analysis:

- **Seed-Art Technique**: Generates unique artwork based on user prompts
- **Artistic DNA Mapping**: Analyzes and replicates artistic styles
- **Style Evolution Tracking**: Monitors development of artistic trends

### CLOE (Curation Engine)

CLOE provides personalized art discovery and market intelligence:

- **Personalization**: Tailors recommendations based on user preferences
- **Market Intelligence**: Analyzes trends and predicts emerging artists
- **Behavioral Analytics**: Understands user behavior to enhance recommendations

### Business Strategist (HORACE)

The Business Strategist agent offers financial and strategic guidance:

- **Portfolio Management**: Helps artists and collectors manage their portfolios
- **Growth Strategy**: Provides actionable insights for career development
- **Risk Assessment**: Evaluates investment opportunities and market risks

## TOLA Token Integration

VORTEX integrates the TOLA token, a Solana-based SPL token with 50M total supply:

- **Art Purchases**: Used for buying and selling artwork
- **Rewards System**: Earned through platform participation
- **Governance Voting**: Enables community decision-making
- **TOLA of the Day**: Daily AI artwork with community revenue sharing

## API Endpoints

### THORIUS API

```
POST /vortex/v1/thorius/query
```
Process a user query with optimal agent selection.

```
POST /vortex/v1/thorius/collaborative
```
Process a complex query using multiple agents collaboratively.

```
GET /vortex/v1/thorius/status
```
Get the status of all AI agents.

```
POST /vortex/v1/thorius/admin/query
```
Process an admin query with access to advanced analytics.

### HURAII API

```
POST /vortex-ai/v1/huraii/generate
```
Generate artwork based on user prompts.

```
GET /vortex-ai/v1/huraii/styles
```
Get available artistic styles.

```
GET /vortex-ai/v1/huraii/artists
```
Get available artist influences.

### CLOE API

```
GET /vortex-ai/v1/market-data
```
Get market overview data.

```
GET /vortex-ai/v1/market-trends
```
Get current market trends.

```
GET /vortex-ai/v1/artist-insights/{id}
```
Get insights for a specific artist.

## Production Installation

### Prerequisites

- **WordPress**: 5.6 or higher
- **PHP**: 8.1 or higher
- **Node.js**: 18+ (for frontend build)
- **Python**: 3.9+ (for AI server)
- **Docker**: For containerized deployment

### Step 1: WordPress Plugin Installation

1. Upload the `vortex-ai-marketplace` folder to the `/wp-content/plugins/` directory
2. Run composer dependencies:
   ```bash
   cd wp-content/plugins/vortex-ai-marketplace
   composer install --no-dev --optimize-autoloader
   ```
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings in the VORTEX AI Marketplace admin panel

### Step 2: Frontend Build Pipeline

Install and build frontend assets:

```bash
# Install Node.js dependencies
npm ci

# Lint JavaScript code
npm run lint

# Build production assets
npm run build
```

### Step 3: Python AI Server Setup

#### Option A: Docker Deployment (Recommended)

```bash
# Build Docker image
docker build -t vortex-ai-server .

# Run container
docker run -d -p 8000:8000 \
  -e AWS_ACCESS_KEY_ID=your_key \
  -e AWS_SECRET_ACCESS_KEY=your_secret \
  -e SOLANA_RPC_URL=your_solana_rpc \
  --name vortex-ai vortex-ai-server
```

#### Option B: Manual Installation

```bash
# Install Python dependencies
pip install -r requirements.txt

# Start AI server
uvicorn server.main:app --host 0.0.0.0 --port 8000
```

## Environment Variables

Configure these environment variables for production:

### AWS Configuration
```bash
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_S3_BUCKET=your_s3_bucket_name
AWS_REGION=us-east-1
```

### Solana Blockchain
```bash
SOLANA_RPC_URL=https://api.mainnet-beta.solana.com
SOLANA_PRIVATE_KEY=your_wallet_private_key
TOLA_TOKEN_MINT=your_tola_token_mint_address
```

### AI Server Configuration
```bash
AI_SERVER_URL=https://your-ai-server.com
AI_SERVER_API_KEY=your_api_key
OPENAI_API_KEY=your_openai_key
STABILITY_API_KEY=your_stability_ai_key
```

## Building the Production ZIP

To create a deployment-ready plugin ZIP:

```bash
# Run build script
npm run build

# Create production ZIP (excludes dev files)
zip -r vortex-ai-marketplace-production.zip . \
  -x "node_modules/*" "tests/*" ".git/*" "*.md" \
  "package*.json" "composer.json" "composer.lock"
```

## Configuration

### API Keys

Set up your API keys in the VORTEX settings panel:

- OpenAI API key for THORIUS and agent functionality
- Stability.ai API key for image generation
- Solana wallet configuration for TOLA token integration

### Database Setup

The plugin automatically creates all necessary database tables during activation. If you encounter any issues, use the database repair tools in the admin panel.

## Usage

### Shortcodes

```
[vortex_thorius_chat]
```
Embed the THORIUS AI chat interface.

```
[vortex_huraii_generator]
```
Embed the HURAII art generation tool.

```
[vortex_artist_dashboard]
```
Display the artist dashboard.

```
[vortex_collector_dashboard]
```
Display the collector dashboard.

### Widgets

- **THORIUS Chat Widget**: AI assistant for your website
- **HURAII Art Generator**: Create AI art directly from your sidebar
- **CLOE Recommendations**: Display personalized art recommendations
- **TOLA Balance**: Show user's TOLA token balance

## Development

### Directory Structure

```
vortex-ai-marketplace/
├── admin/                  # Admin interface files
├── includes/               # Core functionality
│   ├── agents/             # AI agent classes
│   ├── api/                # API endpoint classes
│   ├── blockchain/         # Blockchain integration
│   └── db/                 # Database models and migrations
├── public/                 # Public-facing functionality
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── partials/           # Template partials
└── languages/              # Internationalization files
```

### Adding New Agents

To add a new agent to the THORIUS orchestration system:

1. Create a new agent class in `includes/agents/`
2. Register the agent in `class-vortex-thorius-orchestrator.php`
3. Add domain-specific keywords in the `analyze_domain_distribution()` method
4. Create API endpoints for the new agent if needed

## Troubleshooting

### Common Issues

- **Database Tables Missing**: Run the database repair tool from the admin panel
- **API Connection Errors**: Verify your API keys in the settings
- **TOLA Token Integration Issues**: Check your Solana wallet configuration

### Debugging

Enable debug mode in your wp-config.php file:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('THORIUS_DEBUG', true);
```

## Roadmap

### 2025 Milestones
- MVP Launch (June)
- Artist Onboarding (July)
- Collector Launch (August)
- Miami Art Week (December)

### 2026 Goals
- NEMS Academy Launch
- Global Exhibitions
- Platform Scaling

### 2027 Vision
- Global Expansion
- Regional Hubs
- International Partnerships

## License

This project is licensed under the GPL-2.0+ License - see the LICENSE file for details.

## Credits

Developed by Marianne Nems and the VORTEX team.
>>>>>>> a8f66794812da14c3f250839d506c51ce209c4ee

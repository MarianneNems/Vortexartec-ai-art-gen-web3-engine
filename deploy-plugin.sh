#!/bin/bash

# VORTEX AI Engine - Plugin Deployment Script
# This script creates a clean plugin ZIP file for WordPress deployment

echo "🚀 VORTEX AI Engine - Plugin Deployment"
echo "======================================"

# Set variables
PLUGIN_NAME="vortex-ai-engine"
VERSION="2.1.0"
BUILD_DIR="build"
PLUGIN_DIR="$BUILD_DIR/$PLUGIN_NAME"

# Clean previous build
echo "🧹 Cleaning previous build..."
rm -rf $BUILD_DIR
mkdir -p $PLUGIN_DIR

# Copy essential plugin files
echo "📁 Copying plugin files..."
cp -r vortex-ai-engine/* $PLUGIN_DIR/
cp -r admin $PLUGIN_DIR/
cp -r includes $PLUGIN_DIR/
cp -r vault-secrets $PLUGIN_DIR/
cp -r assets $PLUGIN_DIR/
cp -r templates $PLUGIN_DIR/
cp -r languages $PLUGIN_DIR/
cp composer.json $PLUGIN_DIR/
cp readme.txt $PLUGIN_DIR/

# Remove unnecessary files
echo "🗑️ Removing unnecessary files..."
rm -rf $PLUGIN_DIR/vendor
rm -rf $PLUGIN_DIR/tests
rm -rf $PLUGIN_DIR/backup-local-files
rm -rf $PLUGIN_DIR/infra
rm -rf $PLUGIN_DIR/solana-program
rm -rf $PLUGIN_DIR/contracts
rm -rf $PLUGIN_DIR/blockchain
rm -rf $PLUGIN_DIR/*.log
rm -rf $PLUGIN_DIR/*.zip
rm -rf $PLUGIN_DIR/*.md
rm -rf $PLUGIN_DIR/*.sh
rm -rf $PLUGIN_DIR/*.ps1
rm -rf $PLUGIN_DIR/*.py
rm -rf $PLUGIN_DIR/*.js
rm -rf $PLUGIN_DIR/*.json
rm -rf $PLUGIN_DIR/*.html
rm -rf $PLUGIN_DIR/*.css

# Install Composer dependencies (if composer is available)
if command -v composer &> /dev/null; then
    echo "📦 Installing Composer dependencies..."
    cd $PLUGIN_DIR
    composer install --no-dev --optimize-autoloader
    cd ../..
else
    echo "⚠️ Composer not found. Please install dependencies manually."
fi

# Create ZIP file
echo "📦 Creating plugin ZIP..."
cd $BUILD_DIR
zip -r "$PLUGIN_NAME-v$VERSION.zip" $PLUGIN_NAME/
cd ..

# Set permissions
echo "🔐 Setting file permissions..."
find $PLUGIN_DIR -type d -exec chmod 755 {} \;
find $PLUGIN_DIR -type f -exec chmod 644 {} \;

# Display results
echo ""
echo "✅ Deployment package created successfully!"
echo "📁 Plugin directory: $PLUGIN_DIR"
echo "📦 ZIP file: $BUILD_DIR/$PLUGIN_NAME-v$VERSION.zip"
echo ""
echo "🚀 Next steps:"
echo "1. Upload the ZIP file to your WordPress site"
echo "2. Go to Plugins > Add New > Upload Plugin"
echo "3. Select the ZIP file and install"
echo "4. Activate the plugin"
echo ""
echo "📊 Package size:"
ls -lh "$BUILD_DIR/$PLUGIN_NAME-v$VERSION.zip"
echo ""
echo "📋 Files included:"
find $PLUGIN_DIR -type f | wc -l | xargs echo "Total files:"
echo "" 
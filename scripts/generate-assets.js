#!/usr/bin/env node

/**
 * Asset Generation Script for Swimming School App
 *
 * This script generates all required icons and images for app store publishing.
 *
 * Prerequisites:
 *   npm install sharp
 *
 * Usage:
 *   node scripts/generate-assets.js
 *
 * Or install globally and run:
 *   npm install -g sharp-cli
 *   sharp -i assets/icon.svg -o assets/icon.png resize 1024 1024
 */

const fs = require('fs');
const path = require('path');

// Check if sharp is available
let sharp;
try {
  sharp = require('sharp');
} catch (e) {
  console.log('Sharp library not found. Install it with: npm install sharp');
  console.log('\nAlternatively, use online tools to convert SVG to PNG:');
  console.log('- https://cloudconvert.com/svg-to-png');
  console.log('- https://svgtopng.com/');
  console.log('\nRequired image sizes:');
  printRequiredSizes();
  process.exit(0);
}

const ASSETS_DIR = path.join(__dirname, '..', 'assets');
const STORE_ASSETS_DIR = path.join(__dirname, '..', 'store-assets');

// Icon sizes needed
const ICON_SIZES = {
  // App icons
  'icon.png': { width: 1024, height: 1024 },
  'adaptive-icon.png': { width: 1024, height: 1024 },
  'notification-icon.png': { width: 96, height: 96 },
  'favicon.png': { width: 48, height: 48 },

  // iOS App Store
  'ios/icon-1024.png': { width: 1024, height: 1024 },

  // Android Play Store
  'android/icon-512.png': { width: 512, height: 512 },
  'android/feature-graphic.png': { width: 1024, height: 500 },
};

// Splash screen sizes
const SPLASH_SIZES = {
  'splash.png': { width: 1284, height: 2778 },
  'splash-tablet.png': { width: 2048, height: 2732 },
};

// Screenshot sizes for stores
const SCREENSHOT_SIZES = {
  // iPhone 6.7" (iPhone 14 Pro Max, 15 Pro Max)
  'screenshots/iphone-6.7': { width: 1290, height: 2796 },
  // iPhone 6.5" (iPhone 11 Pro Max, XS Max)
  'screenshots/iphone-6.5': { width: 1242, height: 2688 },
  // iPhone 5.5" (iPhone 8 Plus)
  'screenshots/iphone-5.5': { width: 1242, height: 2208 },
  // iPad Pro 12.9"
  'screenshots/ipad-12.9': { width: 2048, height: 2732 },
  // Android Phone
  'screenshots/android-phone': { width: 1080, height: 1920 },
  // Android Tablet
  'screenshots/android-tablet': { width: 1920, height: 1200 },
};

function printRequiredSizes() {
  console.log('\n=== APP ICONS ===');
  Object.entries(ICON_SIZES).forEach(([name, size]) => {
    console.log(`  ${name}: ${size.width}x${size.height}`);
  });

  console.log('\n=== SPLASH SCREENS ===');
  Object.entries(SPLASH_SIZES).forEach(([name, size]) => {
    console.log(`  ${name}: ${size.width}x${size.height}`);
  });

  console.log('\n=== SCREENSHOTS (for stores) ===');
  Object.entries(SCREENSHOT_SIZES).forEach(([name, size]) => {
    console.log(`  ${name}: ${size.width}x${size.height}`);
  });
}

async function generateIcons() {
  console.log('Generating icons...\n');

  const iconSvgPath = path.join(ASSETS_DIR, 'icon.svg');

  if (!fs.existsSync(iconSvgPath)) {
    console.error('Error: icon.svg not found in assets directory');
    return;
  }

  for (const [outputName, size] of Object.entries(ICON_SIZES)) {
    const outputPath = path.join(ASSETS_DIR, outputName);
    const outputDir = path.dirname(outputPath);

    // Create directory if needed
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    try {
      await sharp(iconSvgPath)
        .resize(size.width, size.height)
        .png()
        .toFile(outputPath);

      console.log(`  ✓ Generated ${outputName} (${size.width}x${size.height})`);
    } catch (error) {
      console.error(`  ✗ Failed to generate ${outputName}: ${error.message}`);
    }
  }
}

async function generateSplashScreens() {
  console.log('\nGenerating splash screens...\n');

  const splashSvgPath = path.join(ASSETS_DIR, 'splash.svg');

  if (!fs.existsSync(splashSvgPath)) {
    console.error('Error: splash.svg not found in assets directory');
    return;
  }

  for (const [outputName, size] of Object.entries(SPLASH_SIZES)) {
    const outputPath = path.join(ASSETS_DIR, outputName);

    try {
      await sharp(splashSvgPath)
        .resize(size.width, size.height, { fit: 'contain', background: '#3b82f6' })
        .png()
        .toFile(outputPath);

      console.log(`  ✓ Generated ${outputName} (${size.width}x${size.height})`);
    } catch (error) {
      console.error(`  ✗ Failed to generate ${outputName}: ${error.message}`);
    }
  }
}

async function createStoreAssetFolders() {
  console.log('\nCreating store asset folders...\n');

  const folders = [
    'store-assets/google-play',
    'store-assets/app-store',
    'store-assets/screenshots/iphone-6.7',
    'store-assets/screenshots/iphone-6.5',
    'store-assets/screenshots/iphone-5.5',
    'store-assets/screenshots/ipad',
    'store-assets/screenshots/android-phone',
    'store-assets/screenshots/android-tablet',
  ];

  folders.forEach(folder => {
    const fullPath = path.join(__dirname, '..', folder);
    if (!fs.existsSync(fullPath)) {
      fs.mkdirSync(fullPath, { recursive: true });
      console.log(`  ✓ Created ${folder}`);
    }
  });
}

async function main() {
  console.log('===========================================');
  console.log('  Swimming School App - Asset Generator');
  console.log('===========================================\n');

  await createStoreAssetFolders();
  await generateIcons();
  await generateSplashScreens();

  console.log('\n===========================================');
  console.log('  Asset generation complete!');
  console.log('===========================================');
  console.log('\nNext steps:');
  console.log('1. Review generated images in /assets folder');
  console.log('2. Create screenshots using simulator/emulator');
  console.log('3. Add feature graphic (1024x500) for Google Play');
  console.log('4. Create store listings with descriptions');
}

main().catch(console.error);

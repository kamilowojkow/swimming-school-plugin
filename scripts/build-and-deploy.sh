#!/bin/bash

# ============================================
# Swimming School App - Build & Deploy Script
# ============================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "============================================"
echo "  Swimming School App - Build & Deploy"
echo "============================================"
echo -e "${NC}"

# Check if EAS CLI is installed
if ! command -v eas &> /dev/null; then
    echo -e "${YELLOW}EAS CLI not found. Installing...${NC}"
    npm install -g eas-cli
fi

# Check if logged in to Expo
echo -e "${BLUE}Checking Expo login status...${NC}"
if ! eas whoami &> /dev/null; then
    echo -e "${YELLOW}Not logged in to Expo. Please log in:${NC}"
    eas login
fi

# Function to display menu
show_menu() {
    echo ""
    echo -e "${BLUE}What would you like to do?${NC}"
    echo ""
    echo "  1) Build for Development (iOS + Android)"
    echo "  2) Build for Preview/Testing (iOS + Android)"
    echo "  3) Build for Production (iOS + Android)"
    echo "  4) Build Android APK only (Preview)"
    echo "  5) Build iOS only (Production)"
    echo "  6) Build Android only (Production)"
    echo "  7) Submit to App Store"
    echo "  8) Submit to Google Play"
    echo "  9) Generate assets (icons, splash)"
    echo "  10) Check build status"
    echo "  0) Exit"
    echo ""
    read -p "Enter your choice: " choice
}

# Function to build development
build_development() {
    echo -e "${GREEN}Starting development build...${NC}"
    eas build --profile development --platform all
}

# Function to build preview
build_preview() {
    echo -e "${GREEN}Starting preview build...${NC}"
    eas build --profile preview --platform all
}

# Function to build production
build_production() {
    echo -e "${GREEN}Starting production build...${NC}"
    echo -e "${YELLOW}Make sure you have:"
    echo "  - Updated version in app.json"
    echo "  - Valid certificates and provisioning profiles"
    echo "  - google-services.json for Android"
    echo -e "${NC}"
    read -p "Continue? (y/n): " confirm
    if [ "$confirm" = "y" ]; then
        eas build --profile production --platform all
    fi
}

# Function to build Android APK
build_android_apk() {
    echo -e "${GREEN}Building Android APK...${NC}"
    eas build --profile preview --platform android
}

# Function to build iOS production
build_ios_production() {
    echo -e "${GREEN}Building iOS for production...${NC}"
    eas build --profile production --platform ios
}

# Function to build Android production
build_android_production() {
    echo -e "${GREEN}Building Android for production...${NC}"
    eas build --profile production --platform android
}

# Function to submit to App Store
submit_app_store() {
    echo -e "${GREEN}Submitting to App Store...${NC}"
    echo -e "${YELLOW}Make sure you have:"
    echo "  - Completed App Store Connect app setup"
    echo "  - Valid Apple Developer credentials"
    echo "  - A successful production iOS build"
    echo -e "${NC}"
    read -p "Continue? (y/n): " confirm
    if [ "$confirm" = "y" ]; then
        eas submit --platform ios
    fi
}

# Function to submit to Google Play
submit_google_play() {
    echo -e "${GREEN}Submitting to Google Play...${NC}"
    echo -e "${YELLOW}Make sure you have:"
    echo "  - Created app in Google Play Console"
    echo "  - Service account JSON key file"
    echo "  - A successful production Android build"
    echo -e "${NC}"
    read -p "Continue? (y/n): " confirm
    if [ "$confirm" = "y" ]; then
        eas submit --platform android
    fi
}

# Function to generate assets
generate_assets() {
    echo -e "${GREEN}Generating assets...${NC}"
    if [ -f "scripts/generate-assets.js" ]; then
        node scripts/generate-assets.js
    else
        echo -e "${RED}Asset generation script not found!${NC}"
    fi
}

# Function to check build status
check_status() {
    echo -e "${GREEN}Checking build status...${NC}"
    eas build:list --limit 5
}

# Main loop
while true; do
    show_menu
    case $choice in
        1) build_development ;;
        2) build_preview ;;
        3) build_production ;;
        4) build_android_apk ;;
        5) build_ios_production ;;
        6) build_android_production ;;
        7) submit_app_store ;;
        8) submit_google_play ;;
        9) generate_assets ;;
        10) check_status ;;
        0) echo -e "${GREEN}Goodbye!${NC}"; exit 0 ;;
        *) echo -e "${RED}Invalid option${NC}" ;;
    esac
done

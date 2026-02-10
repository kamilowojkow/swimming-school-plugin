const { withAppBuildGradle } = require('expo/config-plugins');

/**
 * Expo config plugin to add release signing configuration
 * to android/app/build.gradle during prebuild.
 *
 * Reads signing credentials from android/app/keystore.properties.
 * This allows building a signed release APK locally without EAS.
 */

const SIGNING_MARKER = '// RELEASE_SIGNING_CONFIG';

const KEYSTORE_PROPERTIES_LOADER = `
${SIGNING_MARKER}
def keystorePropertiesFile = rootProject.file("app/keystore.properties")
def keystoreProperties = new Properties()
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}
`;

const SIGNING_CONFIG_BLOCK = `
    signingConfigs {
        release {
            if (keystorePropertiesFile.exists()) {
                storeFile file(keystoreProperties['storeFile'])
                storePassword keystoreProperties['storePassword']
                keyAlias keystoreProperties['keyAlias']
                keyPassword keystoreProperties['keyPassword']
            }
        }
    }`;

module.exports = function withAndroidSigning(config) {
    return withAppBuildGradle(config, (config) => {
        if (config.modResults.language === 'groovy') {
            // Skip if already applied
            if (config.modResults.contents.includes(SIGNING_MARKER)) {
                return config;
            }

            // Add properties loader at the top of the file (after apply plugin lines)
            const applyPluginIndex = config.modResults.contents.lastIndexOf('apply plugin:');
            if (applyPluginIndex !== -1) {
                const endOfLine = config.modResults.contents.indexOf('\n', applyPluginIndex);
                config.modResults.contents =
                    config.modResults.contents.slice(0, endOfLine + 1) +
                    KEYSTORE_PROPERTIES_LOADER +
                    config.modResults.contents.slice(endOfLine + 1);
            } else {
                config.modResults.contents = KEYSTORE_PROPERTIES_LOADER + config.modResults.contents;
            }

            // Add signingConfigs block inside android { }
            const androidBlockIndex = config.modResults.contents.indexOf('android {');
            if (androidBlockIndex !== -1) {
                const insertPoint = config.modResults.contents.indexOf('\n', androidBlockIndex);
                config.modResults.contents =
                    config.modResults.contents.slice(0, insertPoint + 1) +
                    SIGNING_CONFIG_BLOCK + '\n' +
                    config.modResults.contents.slice(insertPoint + 1);
            }

            // Change buildTypes.release to use our signingConfig
            config.modResults.contents = config.modResults.contents.replace(
                /buildTypes\s*\{[\s\S]*?release\s*\{/,
                (match) => {
                    if (match.includes('signingConfig')) {
                        return match;
                    }
                    return match + '\n            signingConfig signingConfigs.release';
                }
            );
        }
        return config;
    });
};

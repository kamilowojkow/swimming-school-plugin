const { withAppBuildGradle } = require('expo/config-plugins');

/**
 * Expo config plugin to remove flipper-integration dependency
 * from the Android build.gradle file.
 *
 * This is needed because React Native 0.76+ removed Flipper support,
 * but some Expo templates still include the dependency reference.
 */
module.exports = function withRemoveFlipper(config) {
  return withAppBuildGradle(config, (config) => {
    if (config.modResults.language === 'groovy') {
      config.modResults.contents = config.modResults.contents.replace(
        /\s*implementation\("com\.facebook\.react:flipper-integration"\)\n?/g,
        '\n'
      );
    }
    return config;
  });
};

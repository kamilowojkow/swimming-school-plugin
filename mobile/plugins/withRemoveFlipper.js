const { withAppBuildGradle } = require('expo/config-plugins');

/**
 * Expo config plugin to exclude flipper-integration dependency
 * from the Android build.
 *
 * This is needed because React Native 0.76+ removed Flipper support,
 * but some dependencies or templates may still reference it.
 *
 * We use Gradle's configurations.all to exclude the dependency
 * at the configuration level, which works regardless of where
 * the dependency is declared.
 */
module.exports = function withRemoveFlipper(config) {
  return withAppBuildGradle(config, (config) => {
    if (config.modResults.language === 'groovy') {
      // Remove any direct flipper-integration dependency line if present
      config.modResults.contents = config.modResults.contents.replace(
        /\s*implementation\s*\(?["']com\.facebook\.react:flipper-integration["']?\)?[^\n]*\n?/g,
        '\n'
      );

      // Add configurations block to exclude flipper-integration from all configurations
      // This handles cases where the dependency comes from transitive dependencies
      const excludeBlock = `
// Exclude flipper-integration as it was removed in React Native 0.76+
configurations.all {
    exclude group: 'com.facebook.react', module: 'flipper-integration'
}
`;

      // Check if the exclude block already exists
      if (!config.modResults.contents.includes("exclude group: 'com.facebook.react', module: 'flipper-integration'")) {
        // Add after the android block closing brace, before dependencies
        const androidBlockEnd = config.modResults.contents.lastIndexOf('android {');
        if (androidBlockEnd !== -1) {
          // Find the dependencies block and insert before it
          const dependenciesIndex = config.modResults.contents.indexOf('dependencies {');
          if (dependenciesIndex !== -1) {
            config.modResults.contents =
              config.modResults.contents.slice(0, dependenciesIndex) +
              excludeBlock + '\n' +
              config.modResults.contents.slice(dependenciesIndex);
          } else {
            // If no dependencies block, add at the end
            config.modResults.contents += excludeBlock;
          }
        }
      }
    }
    return config;
  });
};

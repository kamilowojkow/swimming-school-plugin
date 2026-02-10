const { withAppBuildGradle, withProjectBuildGradle } = require('expo/config-plugins');

/**
 * Expo config plugin to exclude flipper-integration dependency
 * from the Android build.
 *
 * This is needed because React Native 0.76+ removed Flipper support,
 * but some dependencies or templates may still reference it.
 *
 * We use Gradle's configurations.all to exclude the dependency
 * at both the root project level and app level, which works
 * regardless of where the dependency is declared.
 */

const EXCLUDE_COMMENT = '// Exclude flipper-integration as it was removed in React Native 0.76+';
const EXCLUDE_MARKER = "exclude group: 'com.facebook.react', module: 'flipper-integration'";

// Block for app/build.gradle
const APP_EXCLUDE_BLOCK = `
${EXCLUDE_COMMENT}
configurations.all {
    ${EXCLUDE_MARKER}
}
`;

// Block for root build.gradle (using allprojects)
const ROOT_EXCLUDE_BLOCK = `
${EXCLUDE_COMMENT}
allprojects {
    configurations.all {
        ${EXCLUDE_MARKER}
    }
}
`;

function withRemoveFlipperFromApp(config) {
  return withAppBuildGradle(config, (config) => {
    if (config.modResults.language === 'groovy') {
      // Remove any direct flipper-integration dependency line if present
      config.modResults.contents = config.modResults.contents.replace(
        /\s*implementation\s*\(?["']com\.facebook\.react:flipper-integration["']?\)?[^\n]*\n?/g,
        '\n'
      );

      // Check if the exclude block already exists
      if (!config.modResults.contents.includes(EXCLUDE_MARKER)) {
        // Find the dependencies block and insert before it
        const dependenciesIndex = config.modResults.contents.indexOf('dependencies {');
        if (dependenciesIndex !== -1) {
          config.modResults.contents =
            config.modResults.contents.slice(0, dependenciesIndex) +
            APP_EXCLUDE_BLOCK + '\n' +
            config.modResults.contents.slice(dependenciesIndex);
        } else {
          // If no dependencies block, add at the end
          config.modResults.contents += APP_EXCLUDE_BLOCK;
        }
      }
    }
    return config;
  });
}

function withRemoveFlipperFromRoot(config) {
  return withProjectBuildGradle(config, (config) => {
    if (config.modResults.language === 'groovy') {
      // Check if the exclude block already exists
      if (!config.modResults.contents.includes(EXCLUDE_MARKER)) {
        // Add at the end of the file
        config.modResults.contents += ROOT_EXCLUDE_BLOCK;
      }
    }
    return config;
  });
}

module.exports = function withRemoveFlipper(config) {
  // Apply to both root and app build.gradle files
  config = withRemoveFlipperFromRoot(config);
  config = withRemoveFlipperFromApp(config);
  return config;
};

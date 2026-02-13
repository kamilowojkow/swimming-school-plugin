const { withAppBuildGradle, withProjectBuildGradle } = require('expo/config-plugins');

/**
 * Expo config plugin to exclude flipper-integration dependency
 * from the Android build.
 *
 * This is needed because React Native 0.76+ removed Flipper support,
 * but some dependencies or templates may still reference it.
 *
 * We use multiple strategies:
 * 1. Gradle's resolutionStrategy to substitute the dependency with react-android
 * 2. Configuration exclusion as a fallback
 * 3. Component selection rules to reject the dependency
 */

const EXCLUDE_COMMENT = '// Exclude flipper-integration as it was removed in React Native 0.76+';
const EXCLUDE_MARKER = "exclude group: 'com.facebook.react', module: 'flipper-integration'";
const RESOLUTION_MARKER = "// FLIPPER_RESOLUTION_STRATEGY";

// Block for app/build.gradle - uses dependency substitution to replace the module entirely
const APP_EXCLUDE_BLOCK = `
${EXCLUDE_COMMENT}
${RESOLUTION_MARKER}
configurations.all {
    ${EXCLUDE_MARKER}
    resolutionStrategy.dependencySubstitution {
        // Replace flipper-integration module with react-android regardless of version
        substitute module('com.facebook.react:flipper-integration') using module('com.facebook.react:react-android:+') because 'flipper-integration was removed in React Native 0.76+'
    }
}
`;

// Block for root build.gradle (using allprojects)
const ROOT_EXCLUDE_BLOCK = `
${EXCLUDE_COMMENT}
${RESOLUTION_MARKER}
allprojects {
    configurations.all {
        ${EXCLUDE_MARKER}
        resolutionStrategy.dependencySubstitution {
            // Replace flipper-integration module with react-android regardless of version
            substitute module('com.facebook.react:flipper-integration') using module('com.facebook.react:react-android:+') because 'flipper-integration was removed in React Native 0.76+'
        }
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

      // Remove old exclusion-only blocks (without resolution strategy)
      // This regex matches the old simple exclusion block
      config.modResults.contents = config.modResults.contents.replace(
        /\n?\/\/ Exclude flipper-integration[^\n]*\nconfigurations\.all \{\n\s*exclude group[^\}]+\}\n?/g,
        '\n'
      );

      // Check if the new resolution strategy block already exists
      if (!config.modResults.contents.includes(RESOLUTION_MARKER)) {
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
      // Remove old exclusion-only blocks (without resolution strategy)
      config.modResults.contents = config.modResults.contents.replace(
        /\n?\/\/ Exclude flipper-integration[^\n]*\nallprojects \{\n\s*configurations\.all \{\n\s*exclude group[^\}]+\}\n\}\n?/g,
        '\n'
      );

      // Check if the new resolution strategy block already exists
      if (!config.modResults.contents.includes(RESOLUTION_MARKER)) {
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

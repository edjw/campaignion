module.exports = function (config) {
  config.set({
    basePath: '',
    browsers: ['PhantomJS'],
    frameworks: ['jasmine-jquery', 'jasmine'],
    files: [
      { pattern: 'node_modules/jquery/tmp/jquery.js', watched: false },
      { pattern: 'node_modules/babel-polyfill/dist/polyfill.js', watched: false },
      'karma.globals.js',
      'campaignion_*/**/js/**/*.js',
      'campaignion_*/**/js/**/*.test.js'
    ],
    exclude: [
      'campaignion_email_to_target/js/messages_widget.js',
      'campaignion_email_to_target/ui_*/*'
    ],
    reporters: ['spec'],
    preprocessors: {
      'campaignion_*/**/js/**/*.es6.js': ['babel'],
      'campaignion_*/**/js/**/*.test.js': ['babel']
    },
    babelPreprocessor: {
      options: {
        presets: ['es2015'],
        sourceMap: 'inline'
      },
      filename: function (file) {
        return file.originalPath.replace(/\.js$/, '.es5.js');
      },
      sourceFileName: function (file) {
        return file.originalPath;
      }
    },
    autoWatch: true
  });
};

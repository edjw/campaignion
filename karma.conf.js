module.exports = function (config) {
  config.set({
    basePath: '',
    browsers: ['PhantomJS'],
    frameworks: ['jasmine-jquery', 'jasmine', 'fixture'],
    files: [
      { pattern: 'node_modules/jquery/tmp/jquery.js', watched: false },
      { pattern: 'node_modules/babel-polyfill/dist/polyfill.js', watched: false },
      'karma.globals.js',
      'campaignion_*/**/js/**/*.js',
      'campaignion_*/**/js/**/*.test.js',
      'campaignion_*/**/js/**/*.fixture.html'
    ],
    exclude: [
      'campaignion_email_to_target/js/messages_widget.js',
      'campaignion_email_to_target/ui_*/*'
    ],
    reporters: ['spec'],
    preprocessors: {
      '**/*.es6.js': ['babel'],
      '**/*.test.js': ['babel'],
      '**/*.html': ['html2js']
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
    html2JsPreprocessor: {
      processPath: function (filePath) {
        var filename = filePath.match(/[^\/]+\.html$/)[0];
        // Serve fixtures under /fixtures/name-without-extensions
        return 'fixtures/' + filename.replace(/\.fixture\.html$/, '');
      }
    },
    autoWatch: true
  });
};

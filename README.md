# Campaignion

## JavaScript tools

### Installation
```
$ npm install
```

### Compile ES6
`.es6.js` files inside `campaignion_*` modules are compiled to `.js` files with the same name, at the same location:
```
$ gulp
$ gulp watch
```

### Lint
```
$ npm run lint
```
### Run tests
```
$ npm test
```

### Apps with own tooling
Apps that have their own processes can be excluded as follows:
* don’t use the `.es6.js` extension
* add paths to `.eslintignore`
* add paths to `exclude` section in `karma.conf.js`

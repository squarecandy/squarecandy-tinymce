# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## 2.1.0 (2025-06-30)


### Features

* add a third ACF toolbar set that's even more basic than Basic ([7534df4](https://github.com/squarecandy/squarecandy-tinymce/commit/7534df4053cd079929b37da59b80ce01a4bf6665))
* add class for custom shortcodes and embeds ([096a532](https://github.com/squarecandy/squarecandy-tinymce/commit/096a5329f59c02c3a22769fa73ebdfe5fef3e330))
* force Open in New Window to always start unchecked ([a03eaf5](https://github.com/squarecandy/squarecandy-tinymce/commit/a03eaf5c0a7d13b6a5f7cf3696f9c72138e14f3a))
* replace youtube and vimeo iframes with link on paste ([7d0a44a](https://github.com/squarecandy/squarecandy-tinymce/commit/7d0a44ad181a123135548a30d898569c7b12a8b4))


### Bug Fixes

* $_SERVER vars must be quoted in php 7.4+ ([cd76716](https://github.com/squarecandy/squarecandy-tinymce/commit/cd7671641103bf6a0ebd30897c66bdd07e729bb4))
* add more shortcode subclasses ([fcd7e19](https://github.com/squarecandy/squarecandy-tinymce/commit/fcd7e1959e045eb347a6b9c84969177b060c3ca7))
* add more url validation ([2fe7ce3](https://github.com/squarecandy/squarecandy-tinymce/commit/2fe7ce3bf73e308d5f6d5ae1e28e6d454e5f1e4d))
* add termageddom settings ([f59edea](https://github.com/squarecandy/squarecandy-tinymce/commit/f59edea4fb01a07da8d86a1b603fccca4ff5a261))
* add tilb & rockspring custom embeds ([604310a](https://github.com/squarecandy/squarecandy-tinymce/commit/604310a8560276946173567d08813fc59ad199e1))
* add validation to shortcode dialog ([a31c001](https://github.com/squarecandy/squarecandy-tinymce/commit/a31c00162d91ff9a6c27168e0392b5968615ad8f))
* add version constant & add variables for reused values ([afc31c9](https://github.com/squarecandy/squarecandy-tinymce/commit/afc31c9bca96eed2b770ac23523ac1100fbbead7))
* add version updater ([d22f996](https://github.com/squarecandy/squarecandy-tinymce/commit/d22f9965f5318f9e45c98c4ae72254b2d7478200))
* adjust/add comments for shortcode subclasses ([36185bc](https://github.com/squarecandy/squarecandy-tinymce/commit/36185bcf651f93509d71c4c50636a25b5943394a))
* css file overrides, allow in both child and parent theme. ([000ef96](https://github.com/squarecandy/squarecandy-tinymce/commit/000ef96cad395c0ce67aa721f99ec960a02a95f7))
* delegate link target reset ([676f9c5](https://github.com/squarecandy/squarecandy-tinymce/commit/676f9c59e430438a2a1da23ac690af872ac9bad5))
* don't load frontend-style.css in editor if disabled in settings ([6417447](https://github.com/squarecandy/squarecandy-tinymce/commit/641744703dea04c3226eb3546b388469e3b738b4))
* don't strip br, hr, blockquote on paste ([ed5d019](https://github.com/squarecandy/squarecandy-tinymce/commit/ed5d0196a0d82319c39d5af47839a3fd2965a944))
* expand shortcode button functionality ([2705499](https://github.com/squarecandy/squarecandy-tinymce/commit/2705499a6059206ef2da3bcb2ecf5877e0d6fc66))
* finish setting up Termageddon embed ([f5af515](https://github.com/squarecandy/squarecandy-tinymce/commit/f5af5157b4a05db694a7be55eabfc11918c06ac2))
* fix bugs with paste intercept ([915be67](https://github.com/squarecandy/squarecandy-tinymce/commit/915be676cc10aaf46ec5fffa94eac2b9a55287eb))
* fix bugs, add comments ([5f6bd03](https://github.com/squarecandy/squarecandy-tinymce/commit/5f6bd03fe4979f0d06d5c7eb8a18ab3569ab9e62))
* fix errors when using pasteintercept replace function in shortcode button ([b4977d8](https://github.com/squarecandy/squarecandy-tinymce/commit/b4977d8cad2e6cc5d89fdd8ea1876434c6e46a02))
* fix Failed to initialize plugin error ([aa22523](https://github.com/squarecandy/squarecandy-tinymce/commit/aa22523e367e5632e978b240cbd71841040051df))
* fix filter issue ([f1a0bd1](https://github.com/squarecandy/squarecandy-tinymce/commit/f1a0bd1a5005ea515f1ff2028b807a5f5d067012))
* fix js folder url ([6937e23](https://github.com/squarecandy/squarecandy-tinymce/commit/6937e23eeeb65ecf440a5cd7c584873230438d2f))
* fix vimeo & instagram settings ([2e47391](https://github.com/squarecandy/squarecandy-tinymce/commit/2e47391e9eddcbb50ace61569ea04bdcebdfd285))
* get rid of extract, abstract reused code, linting ([070d1ae](https://github.com/squarecandy/squarecandy-tinymce/commit/070d1ae1f5ed7840199f1be2c67daa5d26876703))
* handle both versions of Termageddon embed ([95b5d6a](https://github.com/squarecandy/squarecandy-tinymce/commit/95b5d6a59399c13146a834ae1c83baf3f0b453f8))
* implement Termageddon & Streamspot shortcode button, fix Fb regex ([7ae14dd](https://github.com/squarecandy/squarecandy-tinymce/commit/7ae14dd96e0571d01f3eb04ec4ea60b5b8e9834d))
* load shortcode button js earlier ([b1c229b](https://github.com/squarecandy/squarecandy-tinymce/commit/b1c229b29280f38760754590499eb794b1647069))
* only allow very restricted TinyMCE on front end (WPForms or other front end uses) ([e54b38a](https://github.com/squarecandy/squarecandy-tinymce/commit/e54b38abb464d633dae97625228c596dfb32a33c))
* override error message css ([83ab768](https://github.com/squarecandy/squarecandy-tinymce/commit/83ab76836b2d1da7badcb6133599b59b0596103f))
* rearrange files ([8ad47df](https://github.com/squarecandy/squarecandy-tinymce/commit/8ad47df631db89c56dc899cd1131d088b54773a5))
* refactor to use URL and VERSION define ([d58e251](https://github.com/squarecandy/squarecandy-tinymce/commit/d58e2519618709abfbb5869ef9e06cbe1009ebf3))
* refactor, clean up, add comments to parent class ([1fbe8c2](https://github.com/squarecandy/squarecandy-tinymce/commit/1fbe8c2e03a6e6f3d2b828d79f13f49d54f45d1a))
* reorganize embed class files ([aad0d11](https://github.com/squarecandy/squarecandy-tinymce/commit/aad0d114802277c6844726442501793131804bf4))
* silly typo ([9a4552c](https://github.com/squarecandy/squarecandy-tinymce/commit/9a4552cfe1773f12a1ad0c73e83be6831f4c12ff))
* update termageddon embed for v2 ([ceaba20](https://github.com/squarecandy/squarecandy-tinymce/commit/ceaba201ccb08b127c44583240a25a9b7ea7389d))
* views 2 use different css override file name ([66e4b73](https://github.com/squarecandy/squarecandy-tinymce/commit/66e4b73640834865b340dddd05d21e2a85caa7ae))
* wrap radio buttons in label ([f37f709](https://github.com/squarecandy/squarecandy-tinymce/commit/f37f709f28b57ff2ac42bb5227a7e43dae3f639b))

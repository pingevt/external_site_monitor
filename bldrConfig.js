
let drupal_modules = [
  "./",
  // "./modules/esm_site/",
  "./modules/esm_test_base/",
  "./modules/esm_test_blc/",
  "./modules/esm_test_html_val/",
  "./modules/esm_test_lighthouse/",
  "./modules/esm_test_pchr/",
  "./modules/esm_test_result_base/",
  "./modules/esm_test_tag_checker/",
  "./modules/esm_test_timing_monitor/",
  "./modules/esm_test_wpt/",
  // "./modules/esm_url_group/",
];

let css_config = [];
let sass_config = [];
let js_config = [];
let images_config = [];

drupal_modules.forEach((val, i) => {
  // css_config.push({
  //   src: val + 'assets/src/css/**/*.css',
  //   dest: val + 'assets/dist/css',
  //   watch: [val + 'assets/src/css/**/*.css'],
  // });

  sass_config.push({
    src: val + 'assets/src/scss/*.scss',
    dest: val + 'assets/dist/css',
    watch: [val + 'assets/src/scss/**/*.scss'],
  });

  js_config.push({
    src: val + 'assets/src/js/**/*.js',
    dest: val + 'assets/dist/js',
    watch: [val + 'assets/src/js/**/*.js'],
  });

  // images_config.push({
  //   src: val + 'assets/src/images/*.{jpg,JPG,jpeg,JPEG,gif,png,svg}',
  //   dest: val + 'assets/dist/images',
  //   watch: [val + 'assets/src/images/**/*'],
  // });
});

// console.log(sass_config);

module.exports = {
  css: css_config,
  sass: sass_config,
  js: js_config,
  images: [], //images_config
  // --------------------- END BASIC CONFIG --------------------- //
  rollup: {
    outputOptions: {
      format: 'iife'
    }
  }
}

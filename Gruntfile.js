module.exports = function(grunt) {

    // Load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        makepot: {
            target: {
                options: {
                    type: 'wp-plugin'
                }
            }
        }
    });

    grunt.loadNpmTasks( 'grunt-wp-i18n' );
    grunt.registerTask('default', [ 'makepot']);
}
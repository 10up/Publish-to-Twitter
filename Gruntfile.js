module.exports = function( grunt ) {

	// Project configuration
	grunt.initConfig( {
		pkg    : grunt.file.readJSON( 'package.JSON' ),
		meta   : {
			banner : '/*! <%= pkg.name %> - v<%= pkg.version %> - Copyright (c) <%= grunt.template.today("yyyy") %> */\n'
		},
		jshint : {
			all     : [
				'Gruntfile.js',
				'js/*.dev.js'
			],
			options: {
				jshintrc: true
			}
		},
		uglify : {
			options       : {
				banner : '<%= meta.banner %>',
				mangle : {
					except : ['jQuery']
				}
			},
			settings_page : { files : { 'js/ptt-settings-page.js' : 'js/ptt-settings-page.dev.js' } }
		}
	} );

	// Load other tasks
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

	// Default task
	grunt.registerTask( 'default', ['jshint', 'uglify'] );

	grunt.util.linefeed = '\n';
};
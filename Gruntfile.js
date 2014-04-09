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
		},
		wp_readme_to_markdown: {
			default: {
				files: {
					'readme.md': 'readme.txt'
				}
			}
		}
	} );

	// Load other tasks
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );

	// Default task
	grunt.registerTask( 'default', ['jshint', 'uglify'] );

	// Additional tasks
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	grunt.util.linefeed = '\n';
};
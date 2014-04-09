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
		},
		pot: {
			options:{
				text_domain: 'tweetpublish',
				package_name: '<%= pkg.name %>',
				package_version: '<%= pkg.version %>',
				dest: 'lang/',
				keywords: ['__', '_e', '__ngettext:1,2', '_n:1,2', '__ngettext_noop:1,2', '_n_noop:1,2', '_x:1,2c',
				           '_nx:4c,1,2', '_nx_noop:4c,1,2', '_ex:1,2c', 'esc_attr__', 'esc_attr_e', 'esc_attr_x:1,2c',
				           'esc_html__', 'esc_html_e', 'esc_html_x:1,2c' ]
			},
			files:{
				src:  [ '*.php', 'includes/**/*.php' ],
				expand: true
			}
		}
	} );

	// Load other tasks
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-pot' );

	// Default task
	grunt.registerTask( 'default', ['jshint', 'uglify'] );

	// Additional tasks
	grunt.registerTask( 'localize', ['pot'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	grunt.util.linefeed = '\n';
};
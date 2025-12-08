# TravelMap Wordpress Plugin

## Download Plugin

```shell
	cd wp-content/plugins
	git clone git@bitbucket.org:travelmap/travelmap-wp-plugin.git travelmap-blog
```

## Internationalization (i18n)

## Generate .pot, .po, .mo files

```shell
	# WordPress tools
	cd [wordpress root directory]
	sudo svn co http://develop.svn.wordpress.org/trunk/tools
	sudo ln -s . src
	# TravelMap plugin directory
	cd wp-content/plugins/travelmap-blog
	php ../../../tools/i18n/makepot.php wp-plugin . languages/travelmap-blog.pot
	cp languages/travelmap-blog.pot languages/travelmap-blog-fr.po
	# Translate travelmap-blog-fr.po file
	msgfmt -o languages/travelmap-blog-fr_FR.mo languages/travelmap-blog-fr.po
	msgfmt -o languages/travelmap-blog-fr_BE.mo languages/travelmap-blog-fr.po
	msgfmt -o languages/travelmap-blog-fr_CA.mo languages/travelmap-blog-fr.po
```

## Deploy

For simple readme changes, git push to `master` and check the GitHub Action `10up/action-wordpress-plugin-asset-update`.

To deploy new versions on WordPress, run:

```bash
git tag v1.0.3
git push origin v1.0.3
```

See: https://github.com/10up/action-wordpress-plugin-deploy

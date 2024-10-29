=== Alternate OpenID for WordPress ===
Contributors: jerryy
Donate link:
Tags: OpenID, comment, login, authentication
Requires at least: 2.5 RC1
Tested up to: 2.5 RC2
Stable tag: 0.04

Allow OpenID based commenters to add their thoughts via comments on your WordPress blog. This plugin is not an OpenID server.

== Description ==

[OpenID.net](http://openid.net) can give scads more information about OpenID than any blurb listed here.

This plugin uses the SimpleOpenID class, written by Remy Sharp, to give your blog's visitors a (hopefully) quick and easy way to use their OpenID credentials to login to your blog and enter comments. This plugin requires the server your WordPress blog lives on to have curl access (with support for SSL) built into the PHP version it is using.

Make certain the default role for new users is subscriber. Visitors that login to leave comments will be automatically added to your users list, so do not give them too much automatic access. You may also want to review your new-comments policy.

This plugin attempts to be as unobtrusive to your WordPress installation as possible. It does not add new tables to your WordPress database and it does not add new columns to existing WordPress database tables. It does add new users to the WordPress users table, using current (at the time of development) WordPress language to do so. This should give administrators, moderators, et cetera full control over new users' identities and comments using the standard WordPress administration panels, instead of having to use other MySQL table editors to make any needed changes.

In the spirit of the keep-it-simple approach, this plugin is only set up to give login access on the comments page. Hopefully this adds to the idea that visitors are logging in to enter comments, not edit posts. Commenters log out using the usual WordPress method.

Administrators should note though, that the idea behind OpenID is slightly at odds with the approach taken by WordPress in allowing comments to be published and in allowing new users to register. OpenID users do not have to supply an email address in the profile they 'give out' to the world, they only have to give out the url for the site providing their authentication. Once a moderator allows comments for a user to be approved, WordPress seems to ignore the setting 'Require name and email address' and will publish comments from the OpenID subscriber even though they have not provided an email address in their profile. In addition, this lack of an email address may affect other plugins, especially those that you use to send emails to your subscribers!


== Installation ==

Note: this version 0.04 is for WordPress version 2.5 RC1 or a newer. It is not intended to be used with WordPress version 2.3, use (Alternate OpenID for WordPress) version 0.03 for WordPress version 2.3.

This section describes how to install the plugin and get it working.

e.g.

* Upload the `alternate-openid` folder to the `/wp-content/plugins/` directory.

* Activate the plugin through the 'Plugins' menu in WordPress.

* Open your theme's Comments.php file in your favorite text editor and find the section that begins:	`<?php if ('open' == $post->comment_status) : ?>`.
Just below this you should find (this varies a bit, it depends on your theme) a part that ends:	`<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>">logged in</a> to post a comment.</p>`.

* Right below that, put the following:

	`<!-- /* added for alternate OpenID plugin */ -->`
	`<?php if (function_exists("openid_url_input_text")) : ?>`
	`<div>`
	`	<form id="commentform" action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post">`
	`		<?php openid_url_input_text(); ?>`
	`	</form>`
	`</div>`
	`<?php endif ?>
	`<!-- /* if function exists "openid_url input text" */ -->`

* Your theme may have forms styled a certain way, if so replace the `<div>` tag with one that is appropriate for your CSS setting, i.e.	`<div class="formcontainer">`

* This portion you add in should be above the line	`<?php else : ?>` 

(the `<?php else : ?>` ends part of the section portion that began with the `<?php if ('open' == $post->comment_status) : ?>` line.

== Frequently Asked Questions ==

= Using either http://www.my-site.domain.com or http://my-site.domain.com for OpenID urls works and so does https://www.my-site.domain.com when I enter it for my OpenId url, but I get an error when I enter https://my-site.domain.com. Why? =

This plugin (by way of the included SimpleOpenID class) uses cURL to communicate between the plugin and the site hosting the OpenID server. Behind the scenes, there have been some decisions made about how cURL operates in certain situations. When cURL goes to a secure site (one that begins with the protocol `https://`) cURL has to decide if the url is valid, so cURL, at the least, looks at the site's server's secure certificate and compares the information found on it to the entered url. If these match, cURL decides thing are okay and the communication continues. In this situation, `https://www.my-site.domain.com` is NOT the same as `https://my-site.domain.com`. If the site's server's secure certificate has been set up as `https://www.my-site.domain.com`, cURL will use that as the 'valid' url and reject using other urls, for your safety. Additionally, cURL can complain and fuss a bit if the secure server's certificate is expired.

== Screenshots ==

1. The login link on bottom of the comments page without the Alternate OpenID addition.
2. This is the same comments page bottom with the Alternate OpenID addition.

== Arbitrary section ==

This plugin was developed under WordPress, version 2.5 running on OS X, version 10.5.2 (64 bit), PHP, version 5.2.5, and cURL, version 7.18.0., with MySQL, version 5.1.23. It was tested against phpMyID, version 0.7, and Clamshell, version 0.6.5.

phpMyID is a single user OpenID server. More information can be found at [phpMyID](http://siege.org/projects/phpMyID)

Clamshell is multiuser OpenID server. More information can be found at [Clamshell](http://wiki.guruj.net/Clamshell!Home)

Information about OS X can be found at [Apple](http://www.apple.com).

Information about PHP can be found at [PHP](http://www.php.net).

Information about the SimpleOpenID class can be found at [Remy Sharp](http://remysharp.com).

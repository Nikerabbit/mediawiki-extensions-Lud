class mediawiki {
  require php

  $vhost = 'sanat.csc.fi'
  $dbuser = 'mediawiki'


  file { ["/www", "/www/$vhost", "/www/$vhost/docroot"]:
    ensure => "directory",
  }

  # php-fpm runs as apache
  file { ["/www/$vhost/logs", "/www/$vhost/cache"]:
    ensure => 'directory',
    owner => 'apache',
    group => 'apache',
  }

  file { "/www/$vhost/docroot/w":
    ensure => 'link',
    target => '/www/mediawiki',
  }

  vcsrepo { "/www/mediawiki":
    ensure   => present,
    provider => git,
    source   => 'https://github.com/Nikerabbit/mediawiki-core.git',
    revision => 'lyydi',
    require  => File["/www/$vhost"],
  }

  file { '/www/mediawiki/composer.json':
    ensure  => present,
    source  => 'puppet:///modules/mediawiki/composer.json',
    require => Vcsrepo['/www/mediawiki'],
  }

  file { '/www/mediawiki/extensions/Sanat':
    ensure  => directory,
    purge   => true,
    recurse => true,
    force   => true,
    source  => 'puppet:///modules/mediawiki/Sanat',
    require => Vcsrepo['/www/mediawiki'],
  }

  include composer
  composer::selfupdate { 'selfupdate_composer': }

  composer::exec { 'extension-install':
    cmd     => 'update',
    cwd     => '/www/mediawiki',
    require => File['/www/mediawiki/LocalSettings.php'],
    before  => Exec['mediawiki-update'],
  }

  exec { 'mediawiki-install-dbpass':
    command => "/usr/bin/openssl rand -base64 -out '/www/$vhost/dbpass' 24",
    cwd     => '/www/mediawiki',
    creates => "/www/$vhost/dbpass",
    require => Vcsrepo['/www/mediawiki'],
  }

  exec { 'mediawiki-install-wikipass':
    command => "/usr/bin/openssl rand -base64 -out '/www/$vhost/wikipass' 24",
    cwd     => '/www/mediawiki',
    creates => "/www/$vhost/wikipass",
    require => Vcsrepo['/www/mediawiki'],
  }

  exec { 'mediawiki-install':
    command => "/usr/bin/php /www/mediawiki/maintenance/install.php \
               --dbname mediawiki --dbuser mediawiki --dbpassfile /www/$vhost/dbpass \
               --passfile /www/$vhost/wikipass --installdbuser root \
                Sanat WikiSysop",
    cwd     => '/www/mediawiki',
    creates => '/www/mediawiki/LocalSettings.php',
    require => [Exec['mediawiki-install-dbpass'], Exec['mediawiki-install-wikipass']],
  }

  file { '/www/mediawiki/LocalSettings.php':
    ensure  => present,
    source  => 'puppet:///modules/mediawiki/LocalSettings.php',
    require => [Exec['mediawiki-install'], File['/www/mediawiki/extensions/Sanat']],
  }

  exec { 'mediawiki-install-post':
    command => "/bin/echo 'echo MWCryptRand::generateHex( 64 );' \
                | php /www/mediawiki/maintenance/eval.php
                > /www/$vhost/secret_key",
    cwd     => '/www/mediawiki',
    creates => "/www/$vhost/secret_key",
    require => Vcsrepo['/www/mediawiki'],
  }

  exec { 'mediawiki-update':
    command => "/usr/bin/php /www/mediawiki/maintenance/update.php --quick",
    cwd     => '/www/mediawiki',
    require => File['/www/mediawiki/LocalSettings.php'],
  }

  exec { 'mediawiki-l10n-update':
    command => "/usr/bin/php /www/mediawiki/maintenance/rebuildLocalisationCache.php --quiet --threads 3",
    cwd     => '/www/mediawiki',
    require => Exec['mediawiki-update'],
  }
}

class php {
  package { ['php-fpm', 'php-cli']:
    ensure => latest,
  }

  package { [
    'php-intl',
    'php-mysql',
    'php-pecl-apc',
    'php-xml',
    ]:
    ensure  => latest,
    require => Package['php-fpm'],
  }

  file { '/etc/php.d/apc.ini':
    content => "extension=apc.so\napc.shm_size=512M",
    require => Package['php-pecl-apc'],
    notify  => Service['php-fpm'],
  }

  service { 'php-fpm':
    ensure     => running,
    enable     => true,
    hasstatus  => true,
    hasrestart => true,
    require    => Package['php-fpm'],
  }
}

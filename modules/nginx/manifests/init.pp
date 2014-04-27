class nginx {
  package { 'nginx':
    ensure  => latest,
    require => Yumrepo['epel'],
  }

  service { 'nginx':
    ensure     => running,
    enable     => true,
    hasstatus  => true,
    hasrestart => true,
    require    => [ Package['nginx'], Service['php-fpm'] ],
  }

  File {
    require => Package['nginx'],
    notify  => Service['nginx'],
  }

  file { '/etc/nginx/nginx.conf':
    source => 'puppet:///modules/nginx/nginx.conf',
  }

  file { '/etc/nginx/mime.types':
    source => 'puppet:///modules/nginx/mime.types',
  }

  file { '/etc/nginx/sites':
    ensure => 'directory',
  }

  file { '/etc/nginx/conf.d':
    ensure => 'absent',
    force  => true,
  }

  # The actual sites
  file { '/etc/nginx/sites/sanat.csc.fi':
    source => 'puppet:///modules/nginx/sanat.csc.fi',
  }
}

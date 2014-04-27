class mysql {
  package { 'mysql':
    ensure => latest,
  }

  package { 'mysql-server':
    ensure => latest,
  }

  service { 'mysqld':
    ensure     => running,
    enable     => true,
    hasstatus  => true,
    hasrestart => true,
    require    => Package['mysql-server'],
  }
}

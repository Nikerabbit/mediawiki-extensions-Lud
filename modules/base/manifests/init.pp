class base {

  package {
    [
      'htop',
      'mosh',
    ]:
    ensure => present,
    require => Yumrepo['epel'],
  }
}

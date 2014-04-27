define mediawiki::extension($version = 'master') {
  vcsrepo { "/www/mediawiki/extensions/${name}":
    ensure   => present,
    provider => git,
    source   => "https://github.com/wikimedia/mediawiki-extensions-$name.git",
    revision => $version,
    require  => Vcsrepo['/www/mediawiki'],
    before   => Exec['mediawiki-update'],
  }
}

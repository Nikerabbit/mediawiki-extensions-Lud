node default {
  include epel
  include base
  include php
  include nginx
  include mediawiki
  mediawiki::extension { 'ParserFunctions': }
  mediawiki::extension { 'DeleteBatch': }
  include mysql
  class { 'memcached':
    max_memory => 2048,
    listen_ip  => '127.0.0.1',
  }
}

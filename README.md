Hamlet / DB
===

![CI Status](https://github.com/hamlet-framework/db/workflows/CI/badge.svg?branch=master&event=push)
[![Packagist](https://img.shields.io/packagist/v/hamlet-framework/db.svg)](https://packagist.org/packages/hamlet-framework/db)
[![Packagist](https://img.shields.io/packagist/dt/hamlet-framework/db.svg)](https://packagist.org/packages/hamlet-framework/db)
[![Coverage Status](https://coveralls.io/repos/github/hamlet-framework/db/badge.svg?branch=master)](https://coveralls.io/github/hamlet-framework/db?branch=master)
![Psalm coverage](https://shepherd.dev/github/hamlet-framework/db/coverage.svg?)

Base package for following sub-projects

- hamlet/db-mysql
- hamlet/db-mysql-swoole
- hamlet/db-sqlite3
- hamlet/db-pdo

## Outstanding ToDo

- Run profiler to improve performance
- Add tracing to processor that could be used in every step
- Add test coverage metrics  
- Extract generic tests into a BaseDatabaseTest to be used by all sub-projects

# An index of the MariaDB and MySQL Knowledge bases

[![Actions Status](https://github.com/williamdes/mariadb-mysql-kbs/workflows/Run%20tests/badge.svg)](https://github.com/williamdes/mariadb-mysql-kbs/actions)
[![Actions Status](https://github.com/williamdes/mariadb-mysql-kbs/workflows/Lint%20and%20analyse%20files/badge.svg)](https://github.com/williamdes/mariadb-mysql-kbs/actions)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/e89ffd4b2c8a4f14ae98c282c8934f31)](https://www.codacy.com/gh/williamdes/mariadb-mysql-kbs/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=williamdes/mariadb-mysql-kbs&amp;utm_campaign=Badge_Grade)
[![codecov](https://codecov.io/gh/williamdes/mariadb-mysql-kbs/branch/main/graph/badge.svg)](https://codecov.io/gh/williamdes/mariadb-mysql-kbs)
[![License](https://poser.pugx.org/williamdes/mariadb-mysql-kbs/license)](https://packagist.org/packages/williamdes/mariadb-mysql-kbs)
[![Latest Stable Version](https://poser.pugx.org/williamdes/mariadb-mysql-kbs/v/stable)](https://packagist.org/packages/williamdes/mariadb-mysql-kbs)
[![npm version](https://badge.fury.io/js/mariadb-mysql-kbs.svg)](https://badge.fury.io/js/mariadb-mysql-kbs)
[![Known Vulnerabilities](https://snyk.io/test/github/williamdes/mariadb-mysql-kbs/badge.svg)](https://snyk.io/test/github/williamdes/mariadb-mysql-kbs)
[![Rust Report Card](https://rust-reportcard.xuri.me/badge/github.com/williamdes/mariadb-mysql-kbs)](https://rust-reportcard.xuri.me/report/github.com/williamdes/mariadb-mysql-kbs)

[API doc](https://williamdes.github.io/mariadb-mysql-kbs/Williamdes/MariaDBMySQLKBS.html)

### In this respository you can find some data from knowledge bases

Raw extracted data : `/data/`

Merged data : `/dist/`

### Merged data
- JSON format (raw, slim, ultraslim)
- PHP format (ultraslim)

### Update the data

```bash
cargo run --release extract
```

```bash
composer run build
```

### Install

```bash
composer require williamdes/mariadb-mysql-kbs
```

```bash
npm install --save mariadb-mysql-kbs
```

```bash
yarn add mariadb-mysql-kbs
```

### Packaging status

[![Packaging status](https://repology.org/badge/vertical-allrepos/mariadb-mysql-kbs.svg)](https://repology.org/project/mariadb-mysql-kbs/versions)

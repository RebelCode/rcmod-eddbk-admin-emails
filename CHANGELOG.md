# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD
### Added
- EDD email `bookings` tag shows booking resources and session label, if applicable.

### Changed
- Purchase receipt email shows a list instead of a table.
- Changed booking date time format to include full day-of-the-week name.

## [0.1-alpha3] - 2018-10-30
### Changed
- Now using a services manager instead of the SELECT CQRS resource model.
- The module now depends on the EDD Bookings services module.

## [0.1-alpha2] - 2018-07-31
### Changed
- Module now compatible with latest version of CQRS interface standard.
- Email tag now depends on booking data.

## [0.1-alpha1] - 2018-06-11
Initial version.

# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
## [0.5.1] - 2018-01-25 
### Added
- DF-1275 Initial support for multi-column constraints

## [0.5.0] - 2017-12-26
### Added
- DF-1224 Added ability to set different default limits (max_records_returned) per service
- Added package discovery
### Changed
- DF-1150 Update copyright and support email
- Separated resources from resource handlers

## [0.4.0] - 2017-11-03
### Changed
- Change getNativeDateTimeFormat to handle column schema to detect detailed datetime format
- Move preferred schema naming to service level, add z/OS support
- Add subscription requirements to service provider
- Do not set schema on connection, managed elsewhere

## [0.3.0] - 2017-09-18
### Fixed
- DF-1160 Correct resource name usage for procedures and functions when pulling parameters

## [0.2.0] - 2017-08-17
### Changed
- Reworked API doc usage and generation
- Remove use of schema in alter column
- Correct schema interface overriding
- Set config-based cache prefix

## [0.1.1] - 2017-07-27
### Changed
- Separating base schema from SQL schema
- Fix store proc and func calls
- Datetime settings handling

## 0.1.0 - 2017-06-27
First official release working with the new [df-core](https://github.com/dreamfactorysoftware/df-core) library.

[Unreleased]: https://github.com/dreamfactorysoftware/df-informix/compare/0.5.1...HEAD
[0.5.1]: https://github.com/dreamfactorysoftware/df-informix/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/dreamfactorysoftware/df-informix/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/dreamfactorysoftware/df-informix/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/dreamfactorysoftware/df-informix/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/dreamfactorysoftware/df-informix/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/dreamfactorysoftware/df-informix/compare/0.1.0...0.1.1

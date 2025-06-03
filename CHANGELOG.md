# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v1.3.0] - 2025-03-23

### Breaking Changes in Files/Batches Module
- Changed JSONL file format requirements for batch processing
- Restricted file extensions to `.jsonl` only (removed support for `.json`, `.txt`, `.ndjson`)
- Added stricter validation for file contents and structure

### Changed
- Updated Files API validation to properly handle 'batch' purpose
- Added support for additional MIME types: application/x-jsonlines, application/jsonl, application/x-ndjson, application/x-ndjason
- Updated JSONL file format documentation and examples
- Updated tests to use new JSONL format and purpose
- Improved file validation order in FileManager.php
- Fixed JSONL file validation in FileManager to properly check content format
- Enhanced error messages for file type validation
- Improved test coverage for file upload functionality

### Added
- New example file: examples/batch-processing.php
- Additional test cases for JSONL file validation
- Improved error messages for file validation
- Test results: 39 tests, 218 assertions (all passing)
- Added more comprehensive validation for JSONL file contents
- Support for audio transcription and translation in batch processing
- Support for chat completions in batch processing

### Fixed
- Issue with file type validation in FileManager::validateFile method
- Improved MIME type detection for JSONL files
- Fixed test case for file upload to use correct JSONL format
- Corrected JSONL test file structure to match API requirements

### Migration Guide for Files/Batches Module
If you are using the Files/Batches module, you will need to:
1. Update your JSONL files to include required fields:
   - `custom_id`: Your unique identifier for tracking
   - `method`: Must be "POST"
   - `url`: One of: /v1/chat/completions, /v1/audio/transcriptions, or /v1/audio/translations
   - `body`: Request parameters matching the endpoint format
2. Ensure all files use `.jsonl` extension
3. Update file content to match new validation requirements
4. Review the updated documentation for complete format specifications

## v1.0.0
* [23/03/2025](https://github.com/lucianotonet/groq-php/commits/77391f0c32a9a7602906a2dc1dc31d1313afc858) test: Add EnvironmentVariablesTest for GROQ_API_KEY and GROQ_API_BASE
* [23/03/2025](https://github.com/lucianotonet/groq-php/commits/2ac16eb1b048f11f97642dbea4b0cc900b51715f) fix: prevent TypeError from unset GROQ_API_BASE environment variable
* [23/03/2025](https://github.com/lucianotonet/groq-php/commits/b1d9dd28ff29d5a3feaf6ca6e4238ceac578271d) refactor: Update depracated models on examples and tests to use Llama 3
* [27/02/2025](https://github.com/lucianotonet/groq-php/commits/7335963893c722150a80b25f0a9de5dfffd586a2) docs: Update README
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/a4b1cdaa984feb9f2c985541f46a51dd06a3af6e) fix: Fix general warning - 'The version field is present, it is recommended to leave it out if the package is published on Packagist.'
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/26d11f41ae7084d9df155d80c9ab752afa2e9369) Add GitHub Actions workflow for running tests

## v0.1.2
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/511bbb0b763085cec0c5b129e9a512cd642f449d) chore: Bump version to v0.1.2
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/4b29c9c4446c13ff082678ea24415644c171800b) Merge pull request #11 from lucianotonet/feat/files-and-batches
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/3b3cf6492c09aacb03f906665309b8ab2570e943) Apply suggestions from code review
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/44d0211f257b990cad15e19d9c6fff20608519e6) Apply suggestions from code review
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/7fafc8187286c7f8c23ae81a1aea5ca1aba6e363) Add comprehensive test suite for Groq PHP library
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/3244576e28f1811bca6ca2fb046fa5f95936fe01) Adds reasoning format options to example
* [23/02/2025](https://github.com/lucianotonet/groq-php/commits/21166c741ffe8d649f64048bbfe3d56e08b155df) Adds file and batch processing capabilities

## v0.1.1
* [20/02/2025](https://github.com/lucianotonet/groq-php/commits/048cb936e0eeabc6c350dac03fb02e9a96ff291a) chore: Bump version to v0.1.1
* [20/02/2025](https://github.com/lucianotonet/groq-php/commits/fa075afb5fdab7b57f81170f6dfb25b7247453cf) Improves API key retrieval

## v0.1.0
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/380e3722ba741dbef5647aaac718dac7c399327b) chore: bump version to 0.1.0
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/1ac4a35f87ce2882f7ab4daa18172634c81a13f4) Merge pull request #10 from lucianotonet/develop
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/fcebfec4f6034f12db02abd32debdaca3c52972b) Update temperature input range in reasoning example
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/f733627e6f32279ceecf3b72ad243ea0e4d99d0c) Update README with comprehensive configuration options and new reasoning/tool calling documentation
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/93e46d82c8883ef508899ab264d99dc978b818f3) Renames max_tokens to max_completion_tokens
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/bb700c1acb87d560ea71ea0316c006ca57b44e93) Adds reasoning feature
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/c999f8f734f3db5b680cf225abd970bdc6228207) Merge pull request #9 from lguichard/main
* [18/02/2025](https://github.com/lucianotonet/groq-php/commits/e944b6551c245bbb18ad7883fee228c68649ba34) Fixes
* [14/02/2025](https://github.com/lucianotonet/groq-php/commits/f2330a23c6ce5cc01530c6fb3e4950ab061698f3) Fix - Update completions parameters

## v0.0.10
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/c36c5a471d4caf0cec642887371203c6339d9738) Update version to 0.0.10 in composer.json, add API key options in README.
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/87fae1976b1827ebafcc69986cd729b9ce63cee1) Merge pull request #8 from lucianotonet/develop
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/db48f4475badca9e51aef9f9dacf5892913188ae) Merge branch 'hotfix/test-vision-with-url' into main
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/5f04b293dea29381b90b099a488b1b50ed50b36e) Merge branch 'hotfix/test-vision-with-url' into develop
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/632b93cf05dc7328f366ba5a1a7a6079cb68ee40) Translate messages
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/49ba8cfddeb40d76e5de78ea4dff36ff8a93201e) hotfix: Update image URL in VisionTest
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/e9796cbf8a94cfce642a1b42cb4df6c7e6a658d9) Merge branch 'feature/apikey-on-runtime' into develop
* [29/10/2024](https://github.com/lucianotonet/groq-php/commits/6c17c9146b4097fddda5eb2d81984e0002cbe054) feat: Update Groq class properties and setOptions method.
* [19/09/2024](https://github.com/lucianotonet/groq-php/commits/96062eb2ac9fb40ac3088cd3220825f532778367) feat: Ensure API key is explicitly set during initialization
* [19/09/2024](https://github.com/lucianotonet/groq-php/commits/9ce4c02e4328023e9038c134a3bfeb5ed82a3211) feat(Vision): add default model and enhance analyze method
* [19/09/2024](https://github.com/lucianotonet/groq-php/commits/a50320ea121d4cb9862a53a4d8490e30509e5c5a) feat: add parallel tool calls and additional parameters for flexibility
* [08/09/2024](https://github.com/lucianotonet/groq-php/commits/2f7094aa675f13841046e3bbea1f11a5f4856a6c) Update image URL in README for Groq PHP project

## v0.0.9
* [06/09/2024](https://github.com/lucianotonet/groq-php/commits/d8263d8b0c831d489c69e67554ddfaf98b3e4d8c) Merge remote-tracking branch 'origin/main'
* [06/09/2024](https://github.com/lucianotonet/groq-php/commits/a6939768fd4048de6f264e5d2d86bf2ae57c5f25) Fix: Re-added missing parameters on request
* [06/09/2024](https://github.com/lucianotonet/groq-php/commits/2e790ae361c6499f90cde6a80183bea0c7f973a4) Update README.md
* [05/09/2024](https://github.com/lucianotonet/groq-php/commits/ed15619457b09cd1712c95738889f9e67bf047f6) Update package URL in README for consistency and clarity.
* [05/09/2024](https://github.com/lucianotonet/groq-php/commits/94eec8615b2dfe81072078433d1582fac8abe0cf) Add image to README for Groq PHP project
* [04/09/2024](https://github.com/lucianotonet/groq-php/commits/de8186902da024a163dee5b8aa9163f7eedf867f) Update README.md
* [04/09/2024](https://github.com/lucianotonet/groq-php/commits/0a066d80b719b54e054e5d79052faec8b380ab22) Merge tag 'v0.0.9' and add Vision functionality for image analysis. - Merge tag 'v0.0.9' - Add Vision functionality for image analysis and examples
* [04/09/2024](https://github.com/lucianotonet/groq-php/commits/899f755f5ddc81007f074f3f37229e54f08fa34b) Merge tag 'v0.0.9'
* [04/09/2024](https://github.com/lucianotonet/groq-php/commits/af7a725e45c8b26ec515e7ec0d7d64e83e82bd4f) Merge pull request #6 from tgeorgel/main
* [04/09/2024](https://github.com/lucianotonet/groq-php/commits/948455b251b56e51ae1e1ef172471064771b36ea) Add Vision functionality for image analysis and examples:
* [03/09/2024](https://github.com/lucianotonet/groq-php/commits/cca3e7e073cbe38a5b4cf121fd9ad83761f9eb45) fix: Prevent the language to be forced on the transcript endpoint
* [02/08/2024](https://github.com/lucianotonet/groq-php/commits/6f3ed06528ef3f2da49153ac32bef97568c3fac2) Update Groq PHP package version to 0.0.8

## v0.0.8
* [02/08/2024](https://github.com/lucianotonet/groq-php/commits/5b1ee7018ac4f2f5a521120bfcfbb0f6a72dc011) Update Groq PHP package version to 0.0.8
* [02/08/2024](https://github.com/lucianotonet/groq-php/commits/bc86d82a4c64be6d7118101b49fa3e5fe5779870) feat: Update examples
* [02/08/2024](https://github.com/lucianotonet/groq-php/commits/2a118e3cdbe1ef8305a942c1ca7be2441fb48faf) feat: Enhance error handling and improve API response management
* [30/07/2024](https://github.com/lucianotonet/groq-php/commits/220d8e9b84c95a7dc2ceec9afcc65600df86d292) chore: Update badges in README.md

## v0.0.7
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/24ef05e3b3e0cee44468a3459ddecd6c998b09f9) chore: Bump version to 0.0.7
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/e94092448e8e18f9f88066d617d360f5ab173e46) Test: Validate API key and integrate model listing
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/0692c0be5be92570b953918a7704dab5154866d1) Refactor: Enhance Error Handling and Improve Code Quality
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/0a063da770ba4764625de376338db1689c58316b) docs: Update Changelog
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/0f4c5f021f701e3c9ffaf00283467f98ccf5872d) feat: Add list models feature
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/dca5448832b32fd065b73f1591606aa44374db2d) chore: Update to use $_ENV instead of getenv() for improved reliability
* [23/07/2024](https://github.com/lucianotonet/groq-php/commits/3b585af351edf39897389ed4491b53d5e22ca092) chore: Change GROQ_API_BASE_URL env var to GROQ_API_BASE

## v0.0.6
* [19/07/2024](https://github.com/lucianotonet/groq-php/commits/7c5d736e806bbf8ed6315ae6909670174de9d091) feat: Add speech-to-text transcription and translation features

## v0.0.5
* [04/07/2024](https://github.com/lucianotonet/groq-php/commits/043778f8ec7341789f21e821690555d6dbcd7055) Merge pull request #4 from lucianotonet/versionfix
* [04/07/2024](https://github.com/lucianotonet/groq-php/commits/279d9e8197327f8519aa6ca726d53d81861bff52) Fix version mismatch
* [04/07/2024](https://github.com/lucianotonet/groq-php/commits/8b81ee2fed7644fb756e4baf61507d5a62bf7a04) Update composer.json
* [20/06/2024](https://github.com/lucianotonet/groq-php/commits/eec092f5161f2b59aa59acf9743eaec7c5e42d43) Merge pull request #3 from JosephGabito/main
* [21/06/2024](https://github.com/lucianotonet/groq-php/commits/d072b0906c86686fa75ac458e8af074caf6c17d9) Update README.md
* [13/06/2024](https://github.com/lucianotonet/groq-php/commits/2c7e1fbd8d84db7e71ba0fd21578edd69de4c34d) Docs: Mark Models endpoint as completed (1c9a24d)
* [13/06/2024](https://github.com/lucianotonet/groq-php/commits/2b7707e8ade7ad18713870dda85b8b30dcef7543) Remove unrelated scripts
* [13/06/2024](https://github.com/lucianotonet/groq-php/commits/29d5798121c4eeb5808a575aa3459fe8bf0ebbe5) Docs: Remove max retries from readme
* [13/06/2024](https://github.com/lucianotonet/groq-php/commits/29e854a011cd3bbcc6c8665ba7de1a977c109cf6) Refatoração: Melhora a legibilidade do código em exemplos
* [13/06/2024](https://github.com/lucianotonet/groq-php/commits/8aa9e29a6098f59b6d8449c970f024aab55dd64b) feat: Remove Llama2 references as it is not available anymore
* [18/05/2024](https://github.com/lucianotonet/groq-php/commits/8e0f9f3ad7d48dbcfd2f6eacb3a60137c954af59) Refactored error handling and added documentation examples for Groq PHP library.
* [17/05/2024](https://github.com/lucianotonet/groq-php/commits/941a1d38a70b800be9442a1f59661476eef0cf76) Refactor script filenames for brevity and clarity. Rename CHANGELOG_GENERATOR.sh to CHANGELOG_GEN.sh and CHANGELOG_GENERATOR_AI.sh to CHANGELOG_GEN_AI.sh for concise and consistent naming conventions.
* [17/05/2024](https://github.com/lucianotonet/groq-php/commits/3d833d48267d4940ad34bc00b6a7295116275830) Add Groq API key and base URL to .env.example file
* [16/05/2024](https://github.com/lucianotonet/groq-php/commits/1c9a24dc8f87b1f981f3bc3a11894104b0b41771) Add Models class and list method for fetching models from API

## v0.0.4
* [20/04/2024](https://github.com/lucianotonet/groq-php/commits/3bfb93700e93f51f5d0f393d3f4011015fb63d6a) Add examples folder + some improvements: - Add support for response streaming and JSON mode. - Improve error handling with more descriptive errors. - Add usage examples. - Fix bugs related to streaming and JSON mode. - Update minimum PHP version. - Add unit tests.
* [13/04/2024](https://github.com/lucianotonet/groq-php/commits/aea491db97605365b1128a5c9a6419d5b46f403d) Fix: Cannot assign bool to property LucianoTonet\GroqPHP\Groq::$baseUrl of type string
* [30/03/2024](https://github.com/lucianotonet/groq-php/commits/d90f0c3c1780dc2ee1fd390e3151097a165dc762) Update README

## v0.0.3
* [30/03/2024](https://github.com/lucianotonet/groq-php/commits/98a08261a84c9131b1ad4ce0f75700f5b6939a4f) Add stream funcionality + some refactor
* [30/03/2024](https://github.com/lucianotonet/groq-php/commits/6d42703ee21fd4710ff8514be2ddcced6a27c380) Add stream funcionality + some refactor
* [28/03/2024](https://github.com/lucianotonet/groq-php/commits/e7b608b3a596126785eccc1a902b52a558315548) Add Changelog

## v0.0.2
* [28/03/2024](https://github.com/lucianotonet/groq-php/commits/3c97462ae25b225ced5dd22fc2b92474105279e2) Add some examples and improvements.
* [28/03/2024](https://github.com/lucianotonet/groq-php/commits/b3eaaf6605c7e0bc4572681fc99f3d686ea40ae6) Add license file

## v0.0.1
* [22/03/2024](https://github.com/lucianotonet/groq-php/commits/8fea7dff55eca796dfbc6e1c340ae82c7af0a60f) First commit
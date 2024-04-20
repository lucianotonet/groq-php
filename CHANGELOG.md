## Version 0.0.4

### New Features

* Examples of usage added to the examples directory.

### Improvements

* Error handling improved with more descriptive error messages.
* Support for response streaming.
* Support for stop sequence in streaming.
* Support for JSON mode.

### Bug Fixes

* Fixed an error that prevented response streaming.
* Fixed an error that prevented the use of JSON mode.

### Other Changes

* Minimum PHP requirement updated to 8.1.
* Unit tests added for new features and bug fixes.

## Version 0.0.3

### Fixes
- Added stream functionality + some refactors

## Version 0.0.2

### Fixes
- Fixed the error when passing the 'response_format' parameter along with 'tools', we encountered an error: "response_format` json_object cannot be combined with tool/function calling".

### News
- Introduced examples and other changes:
  - Updated description in README.md
  - Changed the model used in examples/chat.php
  - Added a new file examples/function-calling.php
  - Created a new file examples/index.php
  - Updated the constructor in the Groq class in src/Groq.php

## Version 0.0.1

- First functional version.

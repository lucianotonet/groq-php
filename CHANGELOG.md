
## Version 0.0.2

### Fixes
- Fix the error when passing the 'response_format' parameter along with 'tools', we encountered an error: "response_format` json_object cannot be combined with tool/function calling".

### News
- Introduced examples and other changes:
  - Updated description in README.md
  - Changed the model used in examples/chat.php
  - Added a new file examples/function-calling.php
  - Created a new file examples/index.php
  - Updated the constructor in the Groq class in src/Groq.php

## Version 0.0.1

- First functional version.
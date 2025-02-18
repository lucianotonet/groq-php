## ROADMAP

Planned features for implementation:

### Groq API REST endpoints:

#### ✅ Models Endpoint 

- GET /openai/v1/models
- Returns a list of all active models
- Example: curl https://api.groq.com/openai/v1/models

#### ✅ Chat Completion Endpoint 

- POST /openai/v1/chat/completions
- Performs a chat completion
- Request Body: JSON object with prompt and model fields
- Example: curl -X POST -H "Content-Type: application/json" -d '{"prompt": "Hello", "model": "llama3-8b-8192"}' https://api.groq.com/openai/v1/chat/completions

#### ✅ Transcriptions Endpoint 

- POST /openai/v1/audio/transcriptions
- Transcribes audio files to text
- Request Body: multipart/form-data with file and model fields
- Example: curl -X POST -F "file=@sample_audio.m4a" -F "model=whisper-large-v3" https://api.groq.com/openai/v1/audio/transcriptions

#### ✅ Translations Endpoint 

- POST /openai/v1/audio/translations
- Translates spoken content in an audio file to English
- Request Body: multipart/form-data with file and model fields
- Example: curl -X POST -F "file=@sample_audio.m4a" -F "model=whisper-large-v3" https://api.groq.com/openai/v1/audio/translations

#### ✅ Error Handling 

- The API uses custom HTTP response status codes to indicate API request failures (https://console.groq.com/docs/errors).
- In case of errors, the response body will contain a JSON object with details about the error.
- In cases of json_object type responses with errors, a field called "failed_generation" may be returned with the invalid JSON that caused the error - this is not referenced in the official documentation.

#### ✅ Reasoning Endpoint

- Implemented through the chat completions endpoint
- Provides step-by-step analysis and structured reasoning
- Uses specific prompts to generate detailed and logical responses
- Supports streaming and different models
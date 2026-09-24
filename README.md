# Groq PHP

![Groq PHP](https://raw.githubusercontent.com/lucianotonet/groq-php/main/art.png)

**High-performance PHP client for GroqCloud API**

> Temporary note: full README content is being restored in this commit via follow-up. See branch history for complete docs.

A comprehensive PHP SDK for GroqCloud (GPT-OSS, Qwen 3.8, Whisper, Orpheus, and more).

Using on Laravel? Check this out: [GroqLaravel](https://github.com/lucianotonet/groq-laravel?tab=readme-ov-file#readme)

## Installation

```bash
composer require lucianotonet/groq-php
```

## Configuration

```bash
export GROQ_API_KEY=your_key_here
```

## Notable recent model updates

- Vision default model is `qwen/qwen3.8-27b` (replaces retired `qwen/qwen3.6-27b`).
- Built-in tools: prefer GPT-OSS `tools: [{type: browser_search}, {type: code_interpreter}]` via `BuiltInTools::tools()`. Compound systems were decommissioned.
- TTS defaults to Orpheus (`canopylabs/orpheus-v1-english`).

See package source and examples for full API coverage. Full README will be restored from main with the model-ID patches applied.

## License

[MIT](LICENSE)

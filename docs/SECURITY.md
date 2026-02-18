# Security Policy

## Security considerations for integrators

- **Development tool only**: Twig Inspector is intended for development and debugging. Do not enable it in production; it injects HTML comments and exposes template paths.
- **Template paths**: The overlay shows template names and links that open in your IDE. Ensure your IDE/open-controller URLs are not exposed to untrusted users when the inspector is enabled.

## Bundle security measures

The bundle includes measures to prevent path traversal and unauthorized access:

- **Template name validation**: Rejects path traversal attempts (`..`), null bytes, and absolute paths
- **File path verification**: Ensures resolved template paths are within allowed Twig template directories
- **Parameter validation**: Line numbers must be positive integers
- **Route restrictions**: Routes should only be available in `dev` and `test` environments
- **Prod environment block**: The "open in IDE" controller returns 404 in `prod` even if routes were accidentally enabled (defense in depth)

Routes must be imported with `when@dev:` and `when@test:` in `config/routes.yaml`. The controller checks `kernel.environment` and returns 404 in `prod` without processing the request. The route pattern allows slashes in template names; security validations in the controller prevent path traversal.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in this project, please report it responsibly:

1. **Do not** open a public GitHub issue for security-sensitive bugs.
2. Send details to **[hectorfranco@nowo.tech](mailto:hectorfranco@nowo.tech)** (or the maintainers listed in [composer.json](../composer.json)).
3. Include a clear description, steps to reproduce, and impact if possible.
4. We will acknowledge receipt and work on a fix. We may ask for more information.
5. After a fix is released, we can coordinate on disclosure (e.g. a security advisory).

We appreciate your effort to report vulnerabilities privately so users can update before details are public.

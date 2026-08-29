namespace Eduvixo.Windows.Models;

public sealed record LoginBootstrap(string Email, string Password, bool CaptchaEnabled, byte[]? CaptchaImage);

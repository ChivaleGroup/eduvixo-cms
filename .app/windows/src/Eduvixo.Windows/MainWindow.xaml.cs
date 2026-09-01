using System.ComponentModel;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Windows;
using System.Windows.Automation;
using System.Windows.Controls;
using System.Windows.Media.Imaging;
using Microsoft.Web.WebView2.Core;
using Eduvixo.Windows.Models;
using Eduvixo.Windows.Services;

namespace Eduvixo.Windows;

public partial class MainWindow : Window
{
    private static readonly Uri DashboardUri = new("https://demo.eduvixo.com/dashboard");
    private static readonly Uri WebsiteUri = new("https://www.eduvixo.com/");
    private static readonly Uri WebViewRuntimeUri = new("https://developer.microsoft.com/microsoft-edge/webview2/");

    private readonly LoginSessionService _loginSession = new();
    private AppSettings _settings;
    private string _language;
    private bool _initializingUi = true;
    private bool _browserInitialized;
    private bool _runtimeMissing;
    private bool _captchaEnabled;
    private CancellationTokenSource? _loginCancellation;

    public MainWindow()
    {
        InitializeComponent();

        _settings = SettingsStore.Load();
        _language = _settings.RememberChoice && LocalizationService.IsSupported(_settings.Language)
            ? _settings.Language
            : LocalizationService.DetectSystemLanguage();

        LocalizationService.SetLanguage(_language);
        LanguagePicker.ItemsSource = LocalizationService.Languages;
        LanguagePicker.SelectedValue = _language;
        RememberChoiceCheckBox.IsChecked = _settings.RememberChoice;
        ApplyLocalization();
        _initializingUi = false;
        Loaded += MainWindow_Loaded;
    }

    private async void MainWindow_Loaded(object sender, RoutedEventArgs e)
    {
        if (_settings.RememberChoice && string.Equals(_settings.Mode, "online", StringComparison.Ordinal))
        {
            await ShowLoginAsync();
        }
        else
        {
            OnlineButton.Focus();
        }
    }

    private void ApplyLocalization()
    {
        Title = $"Desktop Client for Windows - {LocalizationService.Get("appTitle")}";
        LanguageLabel.Text = LocalizationService.Get("language");
        ChooseModeTitle.Text = LocalizationService.Get("chooseModeTitle");
        ChooseModeSubtitle.Text = LocalizationService.Get("chooseModeSubtitle");
        OnlineTitle.Text = LocalizationService.Get("onlineTitle");
        OnlineDescription.Text = LocalizationService.Get("onlineDescription");
        OnlineActionText.Text = LocalizationService.Get("openWorkspace");
        OfflineTitle.Text = LocalizationService.Get("offlineTitle");
        OfflineDescription.Text = LocalizationService.Get("offlineDescription");
        OfflineActionText.Text = LocalizationService.Get("offlineAction");
        UnavailableBadge.Text = LocalizationService.Get("comingSoon");
        RememberChoiceCheckBox.Content = LocalizationService.Get("rememberChoice");
        WebsiteButton.Content = LocalizationService.Get("officialWebsite");
        LoadingText.Text = LocalizationService.Get("loading");
        ErrorLauncherButton.Content = LocalizationService.Get("backToStart");
        LoginBackButton.Content = $"← {LocalizationService.Get("backToStart")}";
        LoginWebsiteButton.Content = LocalizationService.Get("officialWebsite");
        LoginEyebrow.Text = LocalizationService.Get("loginEyebrow");
        LoginBrandTitle.Text = LocalizationService.Get("loginBrandTitle");
        LoginBrandDescription.Text = LocalizationService.Get("loginBrandDescription");
        SecureConnectionText.Text = LocalizationService.Get("secureConnection");
        LoginTitle.Text = LocalizationService.Get("loginTitle");
        LoginSubtitle.Text = LocalizationService.Get("loginSubtitle");
        EmailLabel.Text = LocalizationService.Get("emailLabel");
        PasswordLabel.Text = LocalizationService.Get("passwordLabel");
        CaptchaLabel.Text = LocalizationService.Get("captchaLabel");
        RefreshCaptchaButton.Content = LocalizationService.Get("refreshCaptcha");
        DemoAccessNote.Text = LocalizationService.Get("demoAccessNote");
        LoginSubmitButton.Content = LocalizationService.Get("signIn");
        OfflineButton.ToolTip = LocalizationService.Get("offlineUnavailable");

        AutomationProperties.SetName(OnlineButton, LocalizationService.Get("onlineTitle"));
        AutomationProperties.SetHelpText(OnlineButton, LocalizationService.Get("onlineDescription"));
        AutomationProperties.SetName(OfflineButton, LocalizationService.Get("offlineTitle"));
        AutomationProperties.SetHelpText(OfflineButton, LocalizationService.Get("offlineUnavailable"));
        AutomationProperties.SetName(LoginEmailTextBox, LocalizationService.Get("emailLabel"));
        AutomationProperties.SetName(LoginPasswordBox, LocalizationService.Get("passwordLabel"));
        AutomationProperties.SetName(CaptchaTextBox, LocalizationService.Get("captchaLabel"));

        if (ErrorOverlay.Visibility == Visibility.Visible)
        {
            ShowError(_runtimeMissing);
        }
    }

    private void LanguagePicker_SelectionChanged(object sender, SelectionChangedEventArgs e)
    {
        if (_initializingUi || LanguagePicker.SelectedValue is not string code || !LocalizationService.IsSupported(code))
        {
            return;
        }

        _language = code;
        LocalizationService.SetLanguage(code);
        ApplyLocalization();

        if (RememberChoiceCheckBox.IsChecked == true)
        {
            _settings = _settings with { Language = code, RememberChoice = true };
            SettingsStore.Save(_settings);
        }
    }

    private void RememberChoiceCheckBox_Changed(object sender, RoutedEventArgs e)
    {
        if (_initializingUi)
        {
            return;
        }

        var remember = RememberChoiceCheckBox.IsChecked == true;
        _settings = new AppSettings(_language, remember, remember ? _settings.Mode : null);
        SettingsStore.Save(_settings);
    }

    private async void OnlineButton_Click(object sender, RoutedEventArgs e)
    {
        var remember = RememberChoiceCheckBox.IsChecked == true;
        _settings = new AppSettings(_language, remember, remember ? "online" : null);
        SettingsStore.Save(_settings);
        await ShowLoginAsync();
    }

    private async Task ShowLoginAsync(bool sessionExpired = false)
    {
        StartView.Visibility = Visibility.Collapsed;
        BrowserView.Visibility = Visibility.Collapsed;
        LoginView.Visibility = Visibility.Visible;
        SetLoginBusy(true);
        LoginStatusText.Text = sessionExpired
            ? LocalizationService.Get("sessionExpired")
            : LocalizationService.Get("preparingLogin");

        _loginCancellation?.Cancel();
        _loginCancellation?.Dispose();
        _loginCancellation = new CancellationTokenSource();

        try
        {
            var bootstrap = await _loginSession.PrepareAsync(_language, _loginCancellation.Token);
            LoginEmailTextBox.Text = bootstrap.Email;
            LoginPasswordBox.Password = bootstrap.Password;
            _captchaEnabled = bootstrap.CaptchaEnabled;
            CaptchaPanel.Visibility = _captchaEnabled ? Visibility.Visible : Visibility.Collapsed;
            CaptchaLabel.Visibility = _captchaEnabled ? Visibility.Visible : Visibility.Collapsed;
            if (bootstrap.CaptchaImage is not null)
            {
                CaptchaImage.Source = CreateImage(bootstrap.CaptchaImage);
            }

            CaptchaTextBox.Clear();
            LoginStatusText.Text = sessionExpired ? LocalizationService.Get("sessionExpired") : string.Empty;
            SetLoginBusy(false);
            if (_captchaEnabled)
            {
                _ = Dispatcher.BeginInvoke(new Action(() => CaptchaTextBox.Focus()));
            }
            else
            {
                LoginSubmitButton.Focus();
            }
        }
        catch (OperationCanceledException)
        {
        }
        catch
        {
            LoginStatusText.Text = LocalizationService.Get("loginConnectionError");
            SetLoginBusy(false);
        }
    }

    private async Task ShowAuthenticatedBrowserAsync()
    {
        LoginView.Visibility = Visibility.Collapsed;
        StartView.Visibility = Visibility.Collapsed;
        BrowserView.Visibility = Visibility.Visible;
        ErrorOverlay.Visibility = Visibility.Collapsed;
        LoadingOverlay.Visibility = Visibility.Visible;

        if (!_browserInitialized)
        {
            if (!await InitializeBrowserAsync())
            {
                return;
            }
        }

        ImportSessionCookies();
        Browser.CoreWebView2.Navigate(DashboardUri.AbsoluteUri);
    }

    private async Task<bool> InitializeBrowserAsync()
    {
        try
        {
            _ = CoreWebView2Environment.GetAvailableBrowserVersionString();
            var profileDirectory = Path.Combine(
                Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Eduvixo", "WebView2");
            Directory.CreateDirectory(profileDirectory);

            var options = new CoreWebView2EnvironmentOptions(
                additionalBrowserArguments: null,
                language: LocalizationService.BrowserCulture(_language));
            var environment = await CoreWebView2Environment.CreateAsync(null, profileDirectory, options);
            await Browser.EnsureCoreWebView2Async(environment);

            var settings = Browser.CoreWebView2.Settings;
            settings.AreDevToolsEnabled = false;
            settings.AreHostObjectsAllowed = false;
            settings.AreDefaultContextMenusEnabled = true;
            settings.AreBrowserAcceleratorKeysEnabled = true;
            settings.IsBuiltInErrorPageEnabled = false;
            settings.IsPasswordAutosaveEnabled = false;
            settings.IsGeneralAutofillEnabled = false;

            Browser.CoreWebView2.NavigationStarting += Browser_NavigationStarting;
            Browser.CoreWebView2.NavigationCompleted += Browser_NavigationCompleted;
            Browser.CoreWebView2.NewWindowRequested += Browser_NewWindowRequested;
            Browser.CoreWebView2.ProcessFailed += Browser_ProcessFailed;

            _browserInitialized = true;
            return true;
        }
        catch (WebView2RuntimeNotFoundException)
        {
            ShowError(true);
            return false;
        }
        catch
        {
            ShowError(false);
            return false;
        }
    }

    private void Browser_NavigationStarting(object? sender, CoreWebView2NavigationStartingEventArgs e)
    {
        if (!Uri.TryCreate(e.Uri, UriKind.Absolute, out var uri))
        {
            e.Cancel = true;
            ShowError(false, LocalizationService.Get("navigationBlocked"));
            return;
        }

        if (uri.Scheme == "about" || IsEduvixoUri(uri))
        {
            if (IsLoginUri(uri))
            {
                e.Cancel = true;
                _ = Dispatcher.InvokeAsync(async () => await ShowLoginAsync(true));
                return;
            }

            LoadingOverlay.Visibility = Visibility.Visible;
            ErrorOverlay.Visibility = Visibility.Collapsed;
            return;
        }

        e.Cancel = true;
        if (uri.Scheme == Uri.UriSchemeHttps)
        {
            OpenExternal(uri);
        }
        else
        {
            ShowError(false, LocalizationService.Get("navigationBlocked"));
        }
    }

    private void Browser_NavigationCompleted(object? sender, CoreWebView2NavigationCompletedEventArgs e)
    {
        LoadingOverlay.Visibility = Visibility.Collapsed;
        if (e.IsSuccess)
        {
            ErrorOverlay.Visibility = Visibility.Collapsed;
            return;
        }

        ShowError(false, $"{LocalizationService.Get("connectionDescription")} ({e.WebErrorStatus})");
    }

    private void Browser_NewWindowRequested(object? sender, CoreWebView2NewWindowRequestedEventArgs e)
    {
        e.Handled = true;
        if (!Uri.TryCreate(e.Uri, UriKind.Absolute, out var uri))
        {
            return;
        }

        if (uri.Scheme == Uri.UriSchemeHttps)
        {
            OpenExternal(uri);
        }
    }

    private void Browser_ProcessFailed(object? sender, CoreWebView2ProcessFailedEventArgs e) => ShowError(false);

    private void ShowError(bool runtimeMissing, string? description = null)
    {
        _runtimeMissing = runtimeMissing;
        LoadingOverlay.Visibility = Visibility.Collapsed;
        ErrorOverlay.Visibility = Visibility.Visible;
        ErrorTitle.Text = LocalizationService.Get(runtimeMissing ? "runtimeTitle" : "connectionTitle");
        ErrorDescription.Text = description ?? LocalizationService.Get(runtimeMissing ? "runtimeDescription" : "connectionDescription");
        RetryButton.Content = LocalizationService.Get(runtimeMissing ? "installRuntime" : "retry");
    }

    private static bool IsEduvixoUri(Uri uri)
    {
        if (uri.Scheme != Uri.UriSchemeHttps)
        {
            return false;
        }

        return uri.Host.Equals("eduvixo.com", StringComparison.OrdinalIgnoreCase)
               || uri.Host.EndsWith(".eduvixo.com", StringComparison.OrdinalIgnoreCase);
    }

    private static bool IsLoginUri(Uri uri) =>
        IsEduvixoUri(uri) && uri.AbsolutePath.TrimEnd('/').Equals("/login", StringComparison.OrdinalIgnoreCase);

    private void ImportSessionCookies()
    {
        foreach (Cookie cookie in _loginSession.SessionCookies())
        {
            var webCookie = Browser.CoreWebView2.CookieManager.CreateCookie(
                cookie.Name,
                cookie.Value,
                cookie.Domain.TrimStart('.'),
                string.IsNullOrWhiteSpace(cookie.Path) ? "/" : cookie.Path);
            webCookie.IsHttpOnly = cookie.HttpOnly;
            webCookie.IsSecure = cookie.Secure;
            Browser.CoreWebView2.CookieManager.AddOrUpdateCookie(webCookie);
        }
    }

    private static void OpenExternal(Uri uri)
    {
        try
        {
            Process.Start(new ProcessStartInfo(uri.AbsoluteUri) { UseShellExecute = true });
        }
        catch
        {
        }
    }

    private void LauncherButton_Click(object sender, RoutedEventArgs e)
    {
        BrowserView.Visibility = Visibility.Collapsed;
        LoginView.Visibility = Visibility.Collapsed;
        StartView.Visibility = Visibility.Visible;
        OnlineButton.Focus();
    }

    private void LoginBackButton_Click(object sender, RoutedEventArgs e)
    {
        _loginCancellation?.Cancel();
        LoginView.Visibility = Visibility.Collapsed;
        StartView.Visibility = Visibility.Visible;
        OnlineButton.Focus();
    }

    private async void RefreshCaptchaButton_Click(object sender, RoutedEventArgs e)
    {
        await RefreshCaptchaAsync();
    }

    private async Task RefreshCaptchaAsync()
    {
        if (!_captchaEnabled)
        {
            return;
        }

        RefreshCaptchaButton.IsEnabled = false;
        try
        {
            var image = await _loginSession.RefreshCaptchaAsync();
            CaptchaImage.Source = CreateImage(image);
            CaptchaTextBox.Clear();
            CaptchaTextBox.Focus();
        }
        catch
        {
            LoginStatusText.Text = LocalizationService.Get("loginConnectionError");
        }
        finally
        {
            RefreshCaptchaButton.IsEnabled = true;
        }
    }

    private async void LoginSubmitButton_Click(object sender, RoutedEventArgs e)
    {
        if (string.IsNullOrWhiteSpace(LoginEmailTextBox.Text)
            || string.IsNullOrEmpty(LoginPasswordBox.Password)
            || (_captchaEnabled && string.IsNullOrWhiteSpace(CaptchaTextBox.Text)))
        {
            LoginStatusText.Text = LocalizationService.Get("completeLoginFields");
            return;
        }

        SetLoginBusy(true);
        LoginStatusText.Text = LocalizationService.Get("signingIn");
        try
        {
            var result = await _loginSession.LoginAsync(
                LoginEmailTextBox.Text,
                LoginPasswordBox.Password,
                _captchaEnabled ? CaptchaTextBox.Text : string.Empty);
            if (!result.Ok)
            {
                LoginStatusText.Text = LocalizeLoginError(result.Message);
                SetLoginBusy(false);
                await RefreshCaptchaAsync();
                return;
            }

            await ShowAuthenticatedBrowserAsync();
        }
        catch
        {
            LoginStatusText.Text = LocalizationService.Get("loginConnectionError");
            SetLoginBusy(false);
        }
    }

    private static BitmapImage CreateImage(byte[] bytes)
    {
        using var stream = new MemoryStream(bytes);
        var image = new BitmapImage();
        image.BeginInit();
        image.CacheOption = BitmapCacheOption.OnLoad;
        image.StreamSource = stream;
        image.EndInit();
        image.Freeze();
        return image;
    }

    private void SetLoginBusy(bool busy)
    {
        LoginEmailTextBox.IsEnabled = !busy;
        LoginPasswordBox.IsEnabled = !busy;
        CaptchaTextBox.IsEnabled = !busy;
        RefreshCaptchaButton.IsEnabled = !busy;
        LoginSubmitButton.IsEnabled = !busy;
    }

    private static string LocalizeLoginError(string message)
    {
        if (message.Contains("security session", StringComparison.OrdinalIgnoreCase))
        {
            return LocalizationService.Get("securitySessionExpired");
        }

        if (message.Contains("security code", StringComparison.OrdinalIgnoreCase))
        {
            return LocalizationService.Get("captchaIncorrect");
        }

        if (message.Contains("password", StringComparison.OrdinalIgnoreCase))
        {
            return LocalizationService.Get("credentialsIncorrect");
        }

        return LocalizationService.Get("loginUnexpectedError");
    }

    private async void RetryButton_Click(object sender, RoutedEventArgs e)
    {
        if (_runtimeMissing)
        {
            OpenExternal(WebViewRuntimeUri);
            return;
        }

        ErrorOverlay.Visibility = Visibility.Collapsed;
        LoadingOverlay.Visibility = Visibility.Visible;
        if (_browserInitialized)
        {
            Browser.CoreWebView2.Navigate(DashboardUri.AbsoluteUri);
        }
        else
        {
            if (await InitializeBrowserAsync())
            {
                ImportSessionCookies();
                Browser.CoreWebView2.Navigate(DashboardUri.AbsoluteUri);
            }
        }
    }

    private void WebsiteButton_Click(object sender, RoutedEventArgs e) => OpenExternal(WebsiteUri);

    private void Window_Closing(object? sender, CancelEventArgs e)
    {
        _loginCancellation?.Cancel();
        _loginCancellation?.Dispose();
        _loginSession.Dispose();
        Browser.Dispose();
    }
}

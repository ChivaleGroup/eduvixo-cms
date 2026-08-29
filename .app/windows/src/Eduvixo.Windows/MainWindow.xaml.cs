using System.ComponentModel;
using System.Diagnostics;
using System.IO;
using System.Windows;
using System.Windows.Automation;
using System.Windows.Controls;
using Microsoft.Web.WebView2.Core;
using Eduvixo.Windows.Models;
using Eduvixo.Windows.Services;

namespace Eduvixo.Windows;

public partial class MainWindow : Window
{
    private static readonly Uri DashboardUri = new("https://demo.eduvixo.com/dashboard");
    private static readonly Uri WebsiteUri = new("https://www.eduvixo.com/");
    private static readonly Uri WebViewRuntimeUri = new("https://developer.microsoft.com/microsoft-edge/webview2/");

    private AppSettings _settings;
    private string _language;
    private bool _initializingUi = true;
    private bool _browserInitialized;
    private bool _runtimeMissing;

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
            await ShowBrowserAsync();
        }
        else
        {
            OnlineButton.Focus();
        }
    }

    private void ApplyLocalization()
    {
        Title = $"Eduvixo - {LocalizationService.Get("appTitle")}";
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
        BrowserWebsiteButton.Content = LocalizationService.Get("officialWebsite");
        LoadingText.Text = LocalizationService.Get("loading");
        ErrorLauncherButton.Content = LocalizationService.Get("backToStart");
        LauncherButton.ToolTip = LocalizationService.Get("backToStart");
        BackButton.ToolTip = LocalizationService.Get("back");
        ForwardButton.ToolTip = LocalizationService.Get("forward");
        RefreshButton.ToolTip = LocalizationService.Get("refresh");
        OfflineButton.ToolTip = LocalizationService.Get("offlineUnavailable");

        AutomationProperties.SetName(OnlineButton, LocalizationService.Get("onlineTitle"));
        AutomationProperties.SetHelpText(OnlineButton, LocalizationService.Get("onlineDescription"));
        AutomationProperties.SetName(OfflineButton, LocalizationService.Get("offlineTitle"));
        AutomationProperties.SetHelpText(OfflineButton, LocalizationService.Get("offlineUnavailable"));

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
        await ShowBrowserAsync();
    }

    private async Task ShowBrowserAsync()
    {
        StartView.Visibility = Visibility.Collapsed;
        BrowserView.Visibility = Visibility.Visible;
        ErrorOverlay.Visibility = Visibility.Collapsed;
        LoadingOverlay.Visibility = Visibility.Visible;

        if (!_browserInitialized)
        {
            await InitializeBrowserAsync();
            return;
        }

        Browser.CoreWebView2.Navigate(DashboardUri.AbsoluteUri);
    }

    private async Task InitializeBrowserAsync()
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
            Browser.CoreWebView2.HistoryChanged += Browser_HistoryChanged;
            Browser.CoreWebView2.SourceChanged += Browser_SourceChanged;
            Browser.CoreWebView2.NewWindowRequested += Browser_NewWindowRequested;
            Browser.CoreWebView2.ProcessFailed += Browser_ProcessFailed;

            _browserInitialized = true;
            Browser.CoreWebView2.Navigate(DashboardUri.AbsoluteUri);
        }
        catch (WebView2RuntimeNotFoundException)
        {
            ShowError(true);
        }
        catch
        {
            ShowError(false);
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
            UpdateNavigationState();
            return;
        }

        ShowError(false, $"{LocalizationService.Get("connectionDescription")} ({e.WebErrorStatus})");
    }

    private void Browser_HistoryChanged(object? sender, object e) => UpdateNavigationState();

    private void Browser_SourceChanged(object? sender, CoreWebView2SourceChangedEventArgs e)
    {
        if (Uri.TryCreate(Browser.Source?.AbsoluteUri, UriKind.Absolute, out var uri))
        {
            BrowserAddress.Text = uri.Host;
        }
    }

    private void Browser_NewWindowRequested(object? sender, CoreWebView2NewWindowRequestedEventArgs e)
    {
        e.Handled = true;
        if (!Uri.TryCreate(e.Uri, UriKind.Absolute, out var uri))
        {
            return;
        }

        if (IsEduvixoUri(uri))
        {
            Browser.CoreWebView2.Navigate(uri.AbsoluteUri);
        }
        else if (uri.Scheme == Uri.UriSchemeHttps)
        {
            OpenExternal(uri);
        }
    }

    private void Browser_ProcessFailed(object? sender, CoreWebView2ProcessFailedEventArgs e) => ShowError(false);

    private void UpdateNavigationState()
    {
        if (!_browserInitialized)
        {
            return;
        }

        BackButton.IsEnabled = Browser.CoreWebView2.CanGoBack;
        ForwardButton.IsEnabled = Browser.CoreWebView2.CanGoForward;
    }

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
        StartView.Visibility = Visibility.Visible;
        OnlineButton.Focus();
    }

    private void BackButton_Click(object sender, RoutedEventArgs e)
    {
        if (_browserInitialized && Browser.CoreWebView2.CanGoBack)
        {
            Browser.CoreWebView2.GoBack();
        }
    }

    private void ForwardButton_Click(object sender, RoutedEventArgs e)
    {
        if (_browserInitialized && Browser.CoreWebView2.CanGoForward)
        {
            Browser.CoreWebView2.GoForward();
        }
    }

    private void RefreshButton_Click(object sender, RoutedEventArgs e)
    {
        if (_browserInitialized)
        {
            Browser.CoreWebView2.Reload();
        }
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
            await InitializeBrowserAsync();
        }
    }

    private void WebsiteButton_Click(object sender, RoutedEventArgs e) => OpenExternal(WebsiteUri);

    private void Window_Closing(object? sender, CancelEventArgs e) => Browser.Dispose();
}

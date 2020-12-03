class DownloadParametersUtility {
  downloadButton;

  constructor() {
    this.downloadButton = document.querySelector("#download_button");
  }

  setUpDownloadButton() {
    if (this.downloadButton) {
      this.downloadButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.performDownloadRequest();
      };
    }
  }

  async performDownloadRequest() {
    const parameterList = document.querySelector(".param_list");

    if (parameterList) {
      let downloadDenominator = "all_parameters";

      if (parameterList.getAttribute("list")) {
        downloadDenominator = parameterList.getAttribute("list");
      } else if (parameterList.getAttribute("siteaccess")) {
        downloadDenominator = parameterList.getAttribute("siteaccess");
      }
      const downloader = document.querySelector("#downloader");
      downloader.href =
        "/cjw/config-processing/parameter_list/download/" + downloadDenominator;

      downloader.setAttribute(
        "download",
        "parameter_list_" + downloadDenominator
      );
      downloader.click();
      return false;
    }
  }
}

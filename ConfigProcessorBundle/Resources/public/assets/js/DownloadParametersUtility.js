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
      let siteAccessOrAllParameters = "all_parameters";

      if (parameterList.getAttribute("siteaccess")) {
        siteAccessOrAllParameters = parameterList.getAttribute("siteaccess");
      }

      const downloader = document.querySelector("a");
      downloader.href =
        "/cjw/config-processing/parameter_list/download/" +
        siteAccessOrAllParameters;

      downloader.setAttribute(
        "download",
        "parameter_list_" + siteAccessOrAllParameters
      );
      downloader.click();
      return false;

      // const res = await fetch(
      //   "/cjw/config-processing/parameter_list/download/" +
      //     siteAccessOrAllParameters,
      //   {
      //     method: "GET",
      //   }
      // );
      //
      // if (res && res.status !== 200) {
      //   alert("something went wrong with the download");
      //
      //   this.downloadButton.disabled = true;
      // }
    }
  }
}

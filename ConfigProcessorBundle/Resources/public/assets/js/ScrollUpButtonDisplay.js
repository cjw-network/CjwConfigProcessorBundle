class ScrollUpButtonDisplay {
  scrollUpButton;
  // paramDisplayPane;

  constructor() {
    this.scrollUpButton = document.querySelector(".scroll_up_button");
    // this.paramDisplayPane = document.querySelector(".cjw_main_section");
  }

  setUpScrollUpButton() {
    if (this.scrollUpButton) {
      this.scrollUpButton.style.height = 0;
      // this.scrollUpButton.children[0].classList.add("dont_display");

      this.scrollUpButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        window.scroll(0, 0);
      };

      document.addEventListener("scroll", this.handleScroll.bind(this));
    }
  }

  handleScroll() {
    if (window.scrollY === 0) {
      // if (this.paramDisplayPane.scrollTop === 0) {
      this.scrollUpButton.style.height = 0;
      this.scrollUpButton.children[0].classList.add("dont_display");
    } else if (
      this.scrollUpButton.children[0].classList.contains("dont_display")
    ) {
      this.scrollUpButton.children[0].classList.remove("dont_display");
      this.scrollUpButton.style.height = "50px";
    }
  }
}

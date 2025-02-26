document.addEventListener("DOMContentLoaded", function () {
  // Handle file input change for preview
  const fileInputs = document.querySelectorAll(".file-input");
  fileInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const previewContainer = document.querySelector(".media-preview");
      const previewElement = document.querySelector(".media-preview-element");

      if (this.files && this.files[0]) {
        const file = this.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
          previewContainer.style.display = "block";

          if (file.type.startsWith("image/")) {
            if (previewElement.tagName === "VIDEO") {
              const img = document.createElement("img");
              img.src = e.target.result;
              img.className = "media-preview-element";
              previewContainer.replaceChild(img, previewElement);
            } else {
              previewElement.src = e.target.result;
            }
          } else if (file.type.startsWith("video/")) {
            if (previewElement.tagName === "IMG") {
              const video = document.createElement("video");
              video.src = e.target.result;
              video.controls = true;
              video.className = "media-preview-element";
              previewContainer.replaceChild(video, previewElement);
            } else {
              previewElement.src = e.target.result;
            }
          }
        };

        reader.readAsDataURL(file);
      }
    });
  });

  // Handle remove media button
  const removeMediaBtn = document.querySelector(".remove-media-btn");
  if (removeMediaBtn) {
    removeMediaBtn.addEventListener("click", function () {
      const previewContainer = document.querySelector(".media-preview");
      const fileInputs = document.querySelectorAll(".file-input");

      previewContainer.style.display = "none";
      fileInputs.forEach((input) => {
        input.value = "";
      });
    });
  }

  // Handle like and dislike buttons
  const likeButtons = document.querySelectorAll(".like-btn");
  const dislikeButtons = document.querySelectorAll(".dislike-btn");

  likeButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const postId = this.dataset.postId;
      const counterElement = this.querySelector(".like-count");

      fetch("/ssipfix/api/like.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `post_id=${postId}&action=like`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update like count
            counterElement.textContent = data.likes;

            // Toggle active state
            this.classList.toggle("active-like", data.userLiked);

            // Remove active state from dislike button
            const dislikeBtn = document.querySelector(
              `.dislike-btn[data-post-id="${postId}"]`
            );
            const dislikeCounter = dislikeBtn.querySelector(".dislike-count");
            dislikeBtn.classList.remove("active-dislike");
            dislikeCounter.textContent = data.dislikes;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    });
  });

  dislikeButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const postId = this.dataset.postId;
      const counterElement = this.querySelector(".dislike-count");

      fetch("/ssipfix/api/like.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `post_id=${postId}&action=dislike`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update dislike count
            counterElement.textContent = data.dislikes;

            // Toggle active state
            this.classList.toggle("active-dislike", data.userDisliked);

            // Remove active state from like button
            const likeBtn = document.querySelector(
              `.like-btn[data-post-id="${postId}"]`
            );
            const likeCounter = likeBtn.querySelector(".like-count");
            likeBtn.classList.remove("active-like");
            likeCounter.textContent = data.likes;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    });
  });

  // Toggle comment form
  const commentToggleButtons = document.querySelectorAll(".comment-toggle-btn");
  commentToggleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const postId = this.dataset.postId;
      const commentForm = document.querySelector(
        `.comment-form[data-post-id="${postId}"]`
      );
      const commentSection = document.querySelector(
        `.comment-section[data-post-id="${postId}"]`
      );

      commentForm.style.display =
        commentForm.style.display === "none" ? "block" : "none";
      commentSection.style.display =
        commentSection.style.display === "none" ? "block" : "none";
    });
  });

  // Character counter for post and comment input
  const textareas = document.querySelectorAll(".count-chars");
  textareas.forEach((textarea) => {
    textarea.addEventListener("input", function () {
      const counter = document.querySelector(`#${this.dataset.counter}`);
      const maxLength = this.getAttribute("maxlength");
      const remaining = maxLength - this.value.length;

      counter.textContent = `${remaining} karakter tersisa`;

      if (remaining < 20) {
        counter.classList.add("text-danger");
      } else {
        counter.classList.remove("text-danger");
      }
    });
  });
});

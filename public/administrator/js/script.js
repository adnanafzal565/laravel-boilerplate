const editor = {
  format(command) {
    document.execCommand(command, false, null);
  },

  insertHeading(level) {
    document.execCommand('formatBlock', false, 'h' + level);
  },

  insertParagraph() {
    document.execCommand('formatBlock', false, 'p');
  },

  insertLink() {
    const url = prompt("Enter URL:");
    if (url) {
      document.execCommand("createLink", false, url);
    }
  },

  insertImage() {
    const url = prompt("Enter Image URL:");
    if (url) {
      const img = `<img src="${url}" style="max-width:100%;"/>`;
      this.insertHTML(img);
    }
  },

  insertVideo() {
    const url = prompt("Enter YouTube embed URL (iframe):");
    if (url) {
      this.insertHTML(url);
    }
  },

  insertColumns() {
    const columns = parseInt(prompt("Enter number of columns you want to create ?", 0));
    if (isNaN(columns)) {
      return;
    }

    let columnsHTML = `<div style="display: flex; gap: 10px;">`;
      for (let a = 1; a <= columns; a++) {
        columnsHTML += `<div style="flex:1; border: 1px dashed #ccc; padding:10px;">Column ` + a + `</div>`;
      }
    columnsHTML += `</div>`;
    this.insertHTML(columnsHTML);
  },

  insertSection() {
    const sectionHTML = `
      <section style="border: 2px solid #aaa; padding: 10px; margin: 10px 0;">
        <h2>Section Title</h2>
        <p>Section content here...</p>
      </section>
    `;
    this.insertHTML(sectionHTML);
  },

  insertHTML(html) {
    const sel = window.getSelection();
    if (!sel.rangeCount) {
      // No selection exists, just append HTML at end
      document.getElementById('editor').insertAdjacentHTML('beforeend', html);
      return;
    }

    const range = sel.getRangeAt(0);
    const el = document.createElement("div");
    el.innerHTML = html;
    const frag = document.createDocumentFragment();
    let node;
    while ((node = el.firstChild)) {
      frag.appendChild(node);
    }
    range.deleteContents();
    range.insertNode(frag);
  }
};

async function uploadFile(event, callBack = null) {
  event.preventDefault();

  const form = event.currentTarget
  form.submit.setAttribute("disabled", "disabled")

  const formData = new FormData(form)

  try {
    const response = await axios.post(
      baseUrl + "/admin/files/upload",
      formData
    )

    if (response.data.status == "success") {
      const id = response.data.id;
      const filePath = response.data.file_path;
      
      if (callBack != null) {
        callBack({
          id: id,
          filePath: filePath
        });
      }
    } else {
      swal.fire("Error", response.data.message, "error")
    }
  } catch (exp) {
    swal.fire("Error", exp.message, "error")
  } finally {
    form.submit.removeAttribute("disabled")
  }
}

const fileManager = {
  page: 1,
  files: [],
  selected: null,

  init() {
    this.fetchFiles();
  },

  upload(event) {
    const self = this;

    uploadFile(event, function (file) {
      document.querySelector("#tab-upload form").reset();

      let html = "";
      html += self.render({
        id: file.id,
        file_path: file.filePath
      });
      document.querySelector("#tab-existing .media-grid").insertAdjacentHTML("afterbegin", html);

      self.showTab('existing');
    });
  },

  async fetchFiles() {
    try {
      const formData = new FormData();
      formData.append("page", this.page);

      const response = await axios.post(
        baseUrl + "/admin/files",
        formData
      )

      if (response.data.status == "success") {
        this.files = response.data.files;
        let html = "";
        for (let a = 0; a < this.files.length; a++) {
          html += this.render(this.files[a]);
        }
        document.querySelector("#tab-existing .media-grid").innerHTML = html;
      } else {
        swal.fire("Error", response.data.message, "error")
      }
    } catch (exp) {
      swal.fire("Error", exp.message, "error")
    }
  },

  render(file) {
    const parts = file.file_path.split(".");
    let ext = parts[parts.length - 1];

    let html = "";
    html += `<div class="media-item" onclick="fileManager.selectMedia('` + file.id + `', '` + file.file_path + `')">`;
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        html += `<img src="` + file.file_path + `" alt="` + file.name + `" />`;
      } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
        html += `<video src="` + file.file_path + `" muted></video>`;
      } else {
        html += `📄`;
      }
    html += `</div>`;
    return html;
  },

  openMediaModal() {
    document.getElementById('mediaModal').style.display = 'flex';
  },

  closeMediaModal() {
    document.getElementById('mediaModal').style.display = 'none';
  },

  showTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

    document.querySelector(`.tab[onclick="fileManager.showTab('${tab}')"]`).classList.add('active');
    document.getElementById(`tab-${tab}`).style.display = 'block';
  },

  selectMedia(id, url) {
    // Do something with the selected media URL
    // alert("Selected Media URL:\n" + url);
    this.selected = {
      id: id,
      filePath: url
    };
    this.closeMediaModal();

    document.getElementById("preview").setAttribute("src", url);
    document.getElementById("preview").removeAttribute("hidden");
    document.getElementById("btn-remove-featured-image").style.display = "block";
  },

  removeFeaturedImage() {
    this.selected = null;

    document.getElementById("preview").setAttribute("hidden", "hidden");
    document.getElementById("btn-remove-featured-image").style.display = "none";
  }
};

const tags = {
    tagInput: null,
    tagBox: null,
    suggestionsBox: null,
    hiddenInput: null,
    selectedTags: [],

    init() {
        this.tagInput = document.getElementById('tagInput');
        this.tagBox = document.getElementById('tagBox');
        this.suggestionsBox = document.getElementById('suggestions');
        this.hiddenInput = document.getElementById('tagsHidden');

        const self = this;
        this.tagInput.addEventListener('input', function () {
          self.showSuggestions(this.value.trim());
        });

        this.tagInput.addEventListener('keydown', async function (e) {
          if (e.key === ',' || e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim().replace(',', '');
            if (val && !self.selectedTags.includes(val)) {
              self.selectedTags.push(val);
              self.render();
              this.value = '';
              self.suggestionsBox.hidden = true;

              try {
                const formData = new FormData();
                formData.append("tag", val);

                const response = await axios.post(
                  baseUrl + "/admin/tags/add",
                  formData
                )

                if (response.data.status == "success") {
                    // 
                } else {
                  swal.fire("Error", response.data.message, "error")
                }
              } catch (exp) {
                swal.fire("Error", exp.message, "error")
              }
            }
          }
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function (e) {
          if (!self.tagBox.contains(e.target)) {
            self.suggestionsBox.hidden = true;
          }
        });
    },

    render () {
        const self = this;
      // Remove existing tags
      this.tagBox.querySelectorAll('.tag').forEach(tag => tag.remove());

      // Render current tags
      self.selectedTags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag';
        span.innerHTML = tag + `<span onclick="tags.removeTag('${tag}')">&times;</span>`;
        self.tagBox.insertBefore(span, tagInput);
      });

      // Update hidden field
      self.hiddenInput.value = self.selectedTags.join(',');
    },

    removeTag(tag) {
      this.selectedTags = this.selectedTags.filter(t => t !== tag);
      this.render();
    },

    focusInput() {
      this.tagInput.focus();
    },

    showSuggestions(query) {
        const self = this;
      this.suggestionsBox.innerHTML = '';
      if (!query) return this.suggestionsBox.hidden = true;

      const queryWords = query.toLowerCase().split(/\s+/);

        const matches = availableTags.filter(tag => {
          const tagLower = tag.toLowerCase();
          return (
            queryWords.some(word => tagLower.includes(word)) &&
            !self.selectedTags.includes(tag)
          );
        });

      if (matches.length === 0) return this.suggestionsBox.hidden = true;

      matches.forEach(tag => {
        const div = document.createElement('div');
        div.textContent = tag;
        div.onclick = () => {
          self.selectedTags.push(tag);
          self.tagInput.value = '';
          self.render();
          self.suggestionsBox.hidden = true;
        };
        self.suggestionsBox.appendChild(div);
      });

      self.suggestionsBox.hidden = false;
    }
};

async function addCategory(event) {
  const node = event.currentTarget;
  const input = document.getElementById('newCategory');
  const value = input.value.trim();

  if (value === '') return;

  const select = document.getElementById('categories');

  // Check if it already exists
  for (let option of select.options) {
    if (option.value.toLowerCase() === value.toLowerCase()) {
      swal.fire("Error", 'Category already exists.', "error");
      return;
    }
  }

  try {
    node.setAttribute("disabled", "disabled");
    
    const formData = new FormData();
    formData.append("category", value);

    const response = await axios.post(
      baseUrl + "/admin/categories/add",
      formData
    )

    if (response.data.status == "success") {
        // Create and append new option
        const newOption = document.createElement('option');
        newOption.value = value;
        newOption.text = value;
        newOption.selected = true; // Auto-select newly added
        select.appendChild(newOption);

        input.value = '';
    } else {
      swal.fire("Error", response.data.message, "error")
    }
  } catch (exp) {
    swal.fire("Error", exp.message, "error")
  } finally {
    node.removeAttribute("disabled")
  }
}

function generateSlug() {
  let title = document.getElementById('title').value;
  let slug = title.trim().toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '');
  document.getElementById('slug').value = slug;
}

function previewImage() {
  const input = document.getElementById('image');
  const preview = document.getElementById('preview');

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.hidden = false;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function openBase64File(base64String, fileType) {
    // Decode base64 to binary data
    const byteCharacters = atob(base64String);
    const byteNumbers = new Array(byteCharacters.length);
    
    for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], { type: fileType });

    // Create a link pointing to the Blob
    const blobURL = URL.createObjectURL(blob);
    window.open(blobURL, '_blank');
}
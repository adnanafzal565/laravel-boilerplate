const baseUrl = "http://localhost:8000";
const accessTokenKey = "LaravelBoilerplateAccessToken";
const apiKey = "xxx";

const globalState = {
    state: {
        user: null
    },

    listeners: [],

    listen (callBack) {
        this.listeners.push(callBack)
    },

    setState (newState) {
        this.state = {
            ...this.state,
            ...newState
        }

        for (let a = 0; a < this.listeners.length; a++) {
            this.listeners[a](this.state, newState)
        }
    }
}

function get_type_from_path(file = "") {
    const extension = file.split(".").pop().toLowerCase();

    const type = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "svg", "avif"].includes(extension)
        ? "image"
        : ["mp4", "webm", "ogg", "mov", "avi", "mkv", "m4v", "3gp"].includes(extension)
        ? "video"
        : "";

    return type;
}

function ajaxPromise(url, formData) {
    return new Promise(function (resolve, reject) {
        return ajax(url, formData, resolve, reject);
    });
}

async function ajax(
    url,
    formData,
    onSuccess,
    onError,
    onProgress,
    responseType = "json"
) {

    const token = localStorage.getItem(accessTokenKey);
    const noError = ["/me", '/api/me', '/api/messages/fetch'];
    const byPassGuestUrls = ['/login'];

    const final_url = url.startsWith("http")
      ? url
      : baseUrl + url;

    const pathname = new URL(final_url).pathname;

    try {

        if (!formData) {
            formData = new FormData();
        }

        formData.append("timezone", Intl.DateTimeFormat().resolvedOptions().timeZone);
        
        const response = await axios.post(
            final_url,
            formData,
            {
                responseType,
                headers: {
                    Authorization: "Bearer " + token,
                    "x-api-key": apiKey
                },
                onUploadProgress: (progressEvent) => {
                    if (onProgress) {
                        const percent = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );

                        onProgress(percent);
                    }
                }
            }
        );

        if (responseType === "json") {
            if (response.data.status == "success") {
                onSuccess?.(response.data);
            } else {
                if (!noError.includes(pathname)) {
                    swal.fire("Error", response.data.message, "error");
                    onError?.(response.data);
                }
            }
        } else if (responseType === "blob") {
            onSuccess?.(response);
        }
    } catch (exp) {

        console.log(exp)

        if (!noError.includes(pathname)) {
            if (exp.response?.data?.message) {
                swal.fire("Error", exp.response?.data?.message, "error");
            } else if (exp.response?.status == 401) {
                swal.fire("Error", "Unauthorized", "error");
            }
        }

        onError?.(exp.response);
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
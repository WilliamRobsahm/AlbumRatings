
/**
 * Result for API operations (such as adding/editing)
 * @typedef {object} ApiResult
 * @property {boolean} success
 * @property {string} info Information about the result (such as error message)
 */

/**
 * Result for API methods that return a single data object
 * @typedef {object} ApiObjectResult
 * @property {boolean} success
 * @property {string} info Information about the result (such as error message)
 * @property {object} object Fetched data object
 */

/**
 * Result for API methods that return an array of data
 * @typedef {object} ApiDataResult
 * @property {boolean} success
 * @property {string} info Information about the result (such as error message)
 * @property {object[]} data Fetched data array
 */

class API {

    static Config = {
        PATH: "http://localhost:8080/webbutveckling/AlbumRatings/api/index.php",
        LOG_RESULTS: true,
    }
    /**
     * @param {string} moduleName Name of module (ex. Country, Album, Artist)
     * @param {string} methodName Name of method (ex. get, get_list, save)
     * @param {string} data API method parameters
     */
    static async #request(moduleName, methodName, data = {}) {

        const url = [this.Config.PATH, moduleName, methodName].filter(e => e).join("/");
        const logPath = url.replace(this.Config.PATH, "api");

        var parseResult = (r) => {
            return (typeof r == "string" ? JSON.parse(r) :
                r.hasOwnProperty("responseJSON") ? r.responseJSON : r);
        }

        return new Promise((resolve) => {
            $.ajax({ url, data })
            .then(r => {
                const result = parseResult(r);
                if(this.Config.LOG_RESULTS) console.log(logPath, result);
                resolve(result);
            })
            .catch(r => {
                const result = parseResult(r);
                if(this.Config.LOG_RESULTS) console.warn(logPath, result);
                resolve(result);
            })
        })
    }

    static Countries = {
        /** 
         * @param {number} id Country ID
         * @returns {{success: boolean, info: string, object: object}} 
         * */
        get: (id) => this.#request("country", "get", { id }),
        /** 
         * @returns {{success: boolean, info: string, data: object[]}} 
         * */
        getList: () => this.#request("country", "get_list"),
        /** 
         * @param {object} params
         * @param {number} [params.id] Country ID (Insert new country if unset)
         * @param {string} params.name Country Name
         * @returns {{success: boolean, info: string}} 
         * */
        save: (params) => this.#request("country", "save", params),
    }
}

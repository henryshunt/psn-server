var ERROR_HTML = "<div class='status-message'><span>Error Getting Data</span></div>";
var NO_DATA_HTML = "<div class='status-message'><span>Nothing Here</span></div>";

/**
 * Enables calling of a format() function on strings to replace placeholder values
 * in the form {0} with supplied values.
 * Taken from https://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format
 */
if (!String.prototype.format)
{
    String.prototype.format = function()
    {
        var args = arguments;
        return this.replace(/{(\d+)}/g, (match, number) =>
        {
            return (typeof args[number] != "undefined" ? args[number] : match);
        });
    };
}

/**
 * Returns the value of a parameter in the query string, or null if it doesn't exist.
 * Taken from https://davidwalsh.name/query-string-javascript
 * 
 * @param {string} key The key to get the value for.
 */
function getQueryStringValue(key)
{
    key = key.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + key + '=([^&#]*)');
    var results = regex.exec(location.search);

    return (results === null ?
        null : decodeURIComponent(results[1].replace(/\+/g, ' ')));
}

/**
 * Rounds a number to a specific number of decimal places.
 * Taken from https://stackoverflow.com/questions/7342957/how-do-you-round-to-1-decimal-place-in-javascript
 * 
 * @param {float} value The value to round.
 * @param {int} precision The number of places to round to.
 */
function round(value, precision)
{
    var multiplier = Math.pow(10, precision || 0);
    return Math.round(value * multiplier) / multiplier;
}

/**
 * Parses a UTC date time string in MySQL database format into a Moment object
 * and converts it to the time zone specified in the configuration.
 * 
 * @param {string} time A UTC date time string in MySQL format.
 */
function dbTimeToLocal(time)
{
    return moment.utc(time, "YYYY-MM-DD HH:mm:ss").tz(configTimeZone);
}

/**
 * Parses a UTC date time string in MySQL database format into a Moment object.
 * 
 * @param {string} time A UTC date time string in MySQL format.
 */
function dbTimeToUtc(time)
{
    return moment.utc(time, "YYYY-MM-DD HH:mm:ss");
}


function getJson(url)
{
    return new Promise((resolve, reject) =>
    {
        var request = new XMLHttpRequest();
        request.open("GET", url, true);

        request.onload = () =>
        {
            if (request.status >= 200 && request.status < 400)
                resolve(JSON.parse(request.responseText), request.status);
            else reject(JSON.parse(request.responseText), request.status);
        };

        request.onerror = () => reject(null, null);
        request.send();
    });
}

function postJson(url, json)
{
    return new Promise((resolve, reject) =>
    {
        var request = new XMLHttpRequest();
        request.open("POST", url, true);
        request.setRequestHeader("Content-Type", "application/json");

        request.onload = () =>
        {
            if (request.getResponseHeader("Content-Type") === "application/json")
                var data = JSON.parse(request.responseText);
            else var data = null;

            if (request.status >= 200 && request.status < 300)
                resolve(data);
            else reject(data);
        };

        request.onerror = () => reject(null, null);
        request.send(json);
    });
}

function deleteReq(url)
{
    return new Promise((resolve, reject) =>
    {
        var request = new XMLHttpRequest();
        request.open("DELETE", url, true);

        request.onload = () =>
        {
            if (request.status >= 200 && request.status < 400)
                resolve(request.status);
            else reject(request.status);
        };

        request.onerror = () => reject(null);
        request.send();
    });
}

function patchReq(url, data)
{
    return new Promise((resolve, reject) =>
    {
        var request = new XMLHttpRequest();
        request.open("PATCH", url, true);

        request.onload = () =>
        {
            if (request.status >= 200 && request.status < 400)
                resolve(JSON.parse(request.responseText), request.status);
            else reject(JSON.parse(request.responseText), request.status);
        };

        request.onerror = () => reject(null, null);
        request.send(data);
    });
}
// Cache for translations to avoid duplicate API calls
const translationCache = new Map();
// Pending requests queue
const pendingRequests = new Map();
let debounceTimer = null;
const DEBOUNCE_DELAY = 800; // 800ms delay

function parseAlgoliaQueryValue(queryValue, replaceFn) {
	return () => {
		// Check cache first
		if (translationCache.has(queryValue)) {
			replaceFn(translationCache.get(queryValue));
			return Promise.resolve();
		}

		return reverseTranslate(weglotData.api_key, weglotData.current_language, weglotData.original_language, 'https://' + window.location.hostname, queryValue, 1)
			.then(data => {
				if (data && data.to_words[0] !== undefined) {
					translationCache.set(queryValue, data.to_words[0]);
					replaceFn(data.to_words[0]);
				}
			})
	};
}

function parseAlgoliaRequest(algoliaRequest) {
	try {
		const params = new URLSearchParams(algoliaRequest?.params);
		if (params.has("query")) {
			return parseAlgoliaQueryValue(params.get("query"), reverseWord => {
				params.set("query", reverseWord);
				algoliaRequest.params = params.toString();
			});
		}
	} catch (e) {
		console.error(e);
	}
}

document.addEventListener('DOMContentLoaded', function () {
	let lastRequestData = null;
	let requestCounter = 0;

	console.log('[Weglot Algolia] Integration loaded, debounce delay:', DEBOUNCE_DELAY, 'ms');

	xhook.before(function (request, callback) {
		if (!request.url || !request.url.includes('x-algolia-agent') || weglotData.original_language === weglotData.current_language) {
			return callback();
		}

		requestCounter++;
		const currentRequestId = requestCounter;
		console.log(`[Weglot Algolia] 🔵 Request #${currentRequestId} intercepted`);

		let parsedBody;
		try {
			parsedBody = JSON.parse(request.body);
		} catch (error) {
			console.error('[Weglot Algolia] Failed to parse request.body:', error);
			return callback();
		}

		// Extract query for debouncing
		let queryKey = '';
		if (parsedBody.query) {
			queryKey = parsedBody.query;
		} else if (parsedBody.requests && parsedBody.requests[0]?.params) {
			const params = new URLSearchParams(parsedBody.requests[0].params);
			queryKey = params.get("query") || '';
		}

		console.log(`[Weglot Algolia] 📝 Request #${currentRequestId} query: "${queryKey}"`);

		// Clear previous timer
		if (debounceTimer) {
			console.log(`[Weglot Algolia] ⏱️ Clearing previous timer, blocking request`);
			clearTimeout(debounceTimer);
			debounceTimer = null;
		}

		// Store the latest request data (overwrite previous)
		lastRequestData = { request, callback, parsedBody, queryKey, requestId: currentRequestId };
		console.log(`[Weglot Algolia] 💾 Stored request #${currentRequestId}, starting new timer`);

		// Set new timer - only the LAST stored request will be processed
		debounceTimer = setTimeout(() => {
			if (!lastRequestData) {
				console.log('[Weglot Algolia] ⚠️ No request data to process');
				return;
			}

			const { request: finalRequest, callback: finalCallback, parsedBody: finalParsedBody, queryKey: finalQuery, requestId: finalId } = lastRequestData;
			lastRequestData = null;

			console.log(`[Weglot Algolia] ✅ Timer fired! Processing request #${finalId} with query: "${finalQuery}"`);

			// Check cache
			if (translationCache.has(finalQuery)) {
				console.log(`[Weglot Algolia] 🎯 Cache HIT for "${finalQuery}"`);
			} else {
				console.log(`[Weglot Algolia] 🌐 Cache MISS for "${finalQuery}", will call Weglot API`);
			}

			// Process the request
			const callbacks = [];
			if (finalParsedBody.query) {
				callbacks.push(parseAlgoliaQueryValue(finalParsedBody.query, reverseWord => {
					finalParsedBody.query = reverseWord;
					console.log(`[Weglot Algolia] 🔄 Translated query: "${finalQuery}" → "${reverseWord}"`);
				}));
			}

			if (finalParsedBody.requests) {
				for (const algoliaRequest of finalParsedBody.requests) {
					callbacks.push(parseAlgoliaRequest(algoliaRequest));
				}
			}

			let promise = Promise.resolve();
			for (const cb of callbacks) {
				promise = promise.then(() => {
					if (cb) return cb();
				});
			}

			promise.then(() => {
				finalRequest.body = JSON.stringify(finalParsedBody);
				const apiKey = weglotData.api_key.replace('wg_', '');
				const url = finalRequest.url.replace(/^https?:\/\//, '');
				finalRequest.url = 'https://proxy.weglot.com/' + apiKey + '/' + weglotData.original_language + '/' + weglotData.current_language + '/' + url;

				console.log(`[Weglot Algolia] 🚀 Sending proxified request #${finalId} to Weglot proxy`);
				finalCallback();
			});
		}, DEBOUNCE_DELAY);

		// Don't call callback immediately - wait for debounce timer
		// This effectively blocks all requests until the timer fires
		console.log(`[Weglot Algolia] ⏸️ Request #${currentRequestId} blocked, waiting for debounce`);
	});

});

function reverseTranslate(apiKey, l_from, l_to, request_url, word, t) {
	const requestBody = {
		"l_from": l_from,
		"l_to": l_to,
		"request_url": request_url,
		"words": [
			{"w": word, "t": t}
		]
	};

	const apiUrl = `${weglotData.api_url}/translate?api_key=${apiKey}`;

	return fetch(apiUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify(requestBody)
	})
		.then(response => {
			if (!response.ok) {
				throw new Error('Network response was not ok');
			}
			return response.json();
		})
		.catch(error => {
			console.error('There was a problem with your fetch operation:', error);
		});
}

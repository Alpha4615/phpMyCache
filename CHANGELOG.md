# Changes


## 0.2.0-alpha
**Released:** August 17, 2014

* **FIX:** Imported data now checked for validity before work is done with it. Issue #6
* **NEW:** Cache data is now saved as JSON. Issue #5
* **NEW:** Queries can now be delegated directly to database even if cache of that query might exist. Issue #1
* **NEW:** Developer can have a result-set not be cached. Issue #2
* **NEW:** The source of a result set (database or cache) is now provided in metadata. Issue #9
* **NEW:** Developer can specify an error callback to be called when a query fails. Issue #7
* **NEW:** Developer can now access meta data of a result set. Issue #8
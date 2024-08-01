const fs = require('fs').promises;

const readSourceData = async () => {
    const srcObjStr = await fs.readFile(`data/LITURGY__anniversaries.json`, 'utf8');
    const srcObj = JSON.parse(srcObjStr);
    if (srcObj.hasOwnProperty('anniversary_events')) {
        console.log(`retrieved srcObj.anniversary_events: counted ${srcObj.anniversary_events.length} events`);
    }
    ['it', 'en', 'fr','es','de','pt','la'].forEach(lang => {
        checkObjectKeys(srcObj.anniversary_events, lang);
    });
}

const readLangData = async (lang = '') => {
    return await fs.readFile(`data/i18n/${lang}.json`, 'utf8');
}

const checkObjectKeys = async (anniversaryEvents, lang = '') => {
    let count = 0;
    let errorCount = 0;
    const langObjStr = await readLangData(lang);
    const langObj = JSON.parse(langObjStr);

    anniversaryEvents.forEach(event => {
        const key = `${event.event_key}_${event.event_idx}`;
        if(false === langObj.hasOwnProperty(key)) {
            console.log(`lang ${lang} is missing key ${key}`);
            errorCount++;
        }
        count++;
    });
    const langKeyCount = Object.keys(langObj).length;
    console.log(`${count} keys were verified, ${errorCount} errors found; ${lang} has ${langKeyCount} keys`);
}

readSourceData();

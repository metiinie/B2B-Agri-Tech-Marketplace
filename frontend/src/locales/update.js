const fs = require('fs');
const path = require('path');

const updateLocale = (file, additions) => {
    const filePath = path.join(__dirname, file);
    let content = fs.readFileSync(filePath, 'utf8');

    let additionsStr = '';
    for (let k in additions) {
        let val = additions[k].replace(/'/g, "\\'"); // escape single quotes
        additionsStr += '    ' + k + ": '" + val + "',\n";
    }

    // Find 'landing: {' and insert the string
    content = content.replace(/(  landing: \{\n)/, '$1' + additionsStr);

    fs.writeFileSync(filePath, content);
    console.log('Successfully updated ' + file);
}

const enAdditions = {
    navMarketplace: 'Marketplace',
    navFeatures: 'Features',
    navHowItWorks: 'How it Works',
    navGoToDashboard: 'Go to Dashboard',
    navSignIn: 'Sign In',
    navGetStarted: 'Get Started',
    heroMainTitle1: 'The Premier ',
    heroMainTitle2: 'B2B Marketplace',
    heroMainTitle3: 'for Ethiopian Agriculture',
    heroSubtitle: 'Connect directly with verified farmers and rural cooperatives. Secure escrows, seamless logistics, and transparent pricing for modern agri-business.',
    heroAccessDashboard: 'Access Dashboard',
    heroStartTrading: 'Start Trading Now',
    heroExploreFeatures: 'Explore Features',
    marketTitle: 'Explore Marketplace',
    marketSubtitle: 'Find premium agricultural products delivered direct from verified farmers. Log in to explore the journey and purchase.',
    marketNoListings: 'No listings available at the moment.',
    marketSignInToViewAll: 'Sign In to View All',
    featTitle: 'Engineered for Reliability',
    featSubtitle: 'Everything you need to source agricultural products with confidence.',
    feat1Title: 'Secure Escrow Payments',
    feat1Desc: 'Funds are held securely in escrow via Chapa until the buyer verifies the delivery using a secure 1-time PIN.',
    feat2Title: 'Direct from Farmers',
    feat2Desc: 'Bypass middlemen. Source high-quality produce directly from verified farmers and primary union cooperatives.',
    feat3Title: 'Transparent Analytics',
    feat3Desc: 'Access real-time market data, transparent transaction histories, and streamlined dispute resolution processes.',
    howItWorksTitle: 'How It Works',
    how1Title: 'Register',
    how1Desc: 'Join as a Buyer, Farmer, or Transporter',
    how2Title: 'Connect',
    how2Desc: 'Find products or list your harvest',
    how3Title: 'Transact',
    how3Desc: 'Secure payments in Escrow',
    how4Title: 'Deliver',
    how4Desc: 'Confirm delivery with 1-time PIN',
    footerTitle: 'Ready to Modernize Your Supply Chain?',
    footerDesc: 'Join thousands of buyers and farmers actively trading on Ethiopia\'s most secure B2B agri-marketplace.',
    footerCreateAccount: 'Create Free Account',
    footerGoToDashboard: 'Go to Dashboard',
};

const amAdditions = {
    navMarketplace: 'ገበያ',
    navFeatures: 'ባህሪያት',
    navHowItWorks: 'እንዴት እንደሚሰራ',
    navGoToDashboard: 'ወደ ዳሽቦርድ ሂድ',
    navSignIn: 'ግባ',
    navGetStarted: 'አሁን ጀምር',
    heroMainTitle1: 'ቀዳሚው ',
    heroMainTitle2: 'B2B የግብርና ገበያ',
    heroMainTitle3: 'በኢትዮጵያ',
    heroSubtitle: 'ከተረጋገጡ ገበሬዎች እና የገጠር ህብረት ስራ ማህበራት ጋር በቀጥታ ይገናኙ። ለዘመናዊ ግብርና ደህንነቱ የተጠበቀ ክፍያ፣ እንከን የለሽ ሎጂስቲክስ እና ግልፅ የዋጋ አሰጣጥ።',
    heroAccessDashboard: 'ወደ ዳሽቦርድ ሂድ',
    heroStartTrading: 'አሁኑኑ ንግድ ይጀምሩ',
    heroExploreFeatures: 'ባህሪያትን ያስሱ',
    marketTitle: 'ገበያውን ያስሱ',
    marketSubtitle: 'ከገበሬዎች በቀጥታ የሚከፋፈሉ ምርጥ የግብርና ምርቶችን ያግኙ። ለመግዛት እና ሂደት ለመከታተል ይግቡ።',
    marketNoListings: 'በአሁኑ ሰዓት ምንም ምርት የለም።',
    marketSignInToViewAll: 'ሁሉንም ለማየት ይግቡ',
    featTitle: 'ለአስተማማኝነት የተገነባ',
    featSubtitle: 'የግብርና ምርቶችን በልበ ሙሉነት ለመግዛት የሚያስፈልግዎት ነገር ሁሉ።',
    feat1Title: 'ደህንነቱ የተጠበቀ ክፍያ',
    feat1Desc: 'የመረከቢያ ፒን ተረጋገጦ እቃው መድረሱ እስከሚረጋገጥ ድረስ ገንዘብዎ በቻፓ ኢስክሮው ደህንነቱ ተጠብቆ ይቆያል።',
    feat2Title: 'በቀጥታ ከገበሬዎች',
    feat2Desc: 'ደላላን ያስወግዱ። ከፍተኛ ጥራት ያላቸውን ምርቶች በቀጥታ ከተረጋገጡ ገበሬዎች እና ህብረት ስራ ማህበራት ይግዙ።',
    feat3Title: 'ግልፅ መረጃ',
    feat3Desc: 'የቀጥታ ገበያ መረጃዎችን፣ ግልፅ የግብይት ታሪክን እና የተሳለጠ የአለመግባባት መፍቻ ሂደቶችን ያግኙ።',
    howItWorksTitle: 'እንዴት እንደሚሰራ',
    how1Title: 'ይመዝገቡ',
    how1Desc: 'እንደ ገዢ፣ ገበሬ፣ ወይም አጓጓዥ ይቀላቀሉ',
    how2Title: 'ይገናኙ',
    how2Desc: 'ምርቶችን ያግኙ ወይም የእርስዎን ዝርዝር ይጨምሩ',
    how3Title: 'ይገበያዩ',
    how3Desc: 'ክፍያዎን በኢስክሮው ደህንነት ይጠብቁ',
    how4Title: 'ይረከቡ',
    how4Desc: 'በ1-ጊዜ ፒን ምርት መረከብዎን ያረጋግጡ',
    footerTitle: 'የአቅርቦት ሰንሰለትዎን ለማዘመን ዝግጁ ነዎት?',
    footerDesc: 'በኢትዮጵያ እጅግ አስተማማኝ በሆነው የግብርና ገበያ ላይ በመገበያየት ላይ ያሉ በሺዎች የሚቆጠሩ ገዥዎችን እና ገበሬዎችን ይቀላቀሉ።',
    footerCreateAccount: 'ነፃ መለያ ይፍጠሩ',
    footerGoToDashboard: 'ወደ ዳሽቦርድ ሂድ',
};

try {
    updateLocale('en.js', enAdditions);
    updateLocale('am.js', amAdditions);
} catch (e) {
    console.error('Error', e);
}

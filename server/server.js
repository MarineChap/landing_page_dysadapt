const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const db = require('./database');

const app = express();
const PORT = process.env.PORT || 3000;

// Security Middleware
app.use(helmet()); // Sets various HTTP headers for security
// CORS Configuration
const allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:5500', // Common VS Code Live Server port
    'http://127.0.0.1:5500',
    'null', // Allow direct file opening (file://)
    'https://dysapp.com',
    'https://www.dysapp.com'
];

const corsOptions = {
    origin: function (origin, callback) {
        // Allow requests with no origin (like mobile apps or curl requests)
        if (!origin) return callback(null, true);
        if (allowedOrigins.indexOf(origin) !== -1) {
            callback(null, true);
        } else {
            callback(new Error('Not allowed by CORS'));
        }
    }
};

app.use(cors(corsOptions)); // Enable CORS with restricted options
app.use(express.json()); // Parse JSON bodies

// Rate Limiting (Prevent abuse)
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // Limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later.'
});
app.use('/api', limiter);

// Routes
app.post('/api/subscribe', (req, res) => {
    let { email, consent } = req.body;

    // Sanitize input
    if (typeof email === 'string') {
        email = email.trim().toLowerCase();
    }

    // Advanced Email Validation Regex
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (!email || !emailRegex.test(email)) {
        return res.status(400).json({ error: 'Email invalide.' });
    }
    if (!consent) {
        return res.status(400).json({ error: 'Le consentement est requis.' });
    }

    // Insert into DB
    const stmt = db.prepare("INSERT INTO subscribers (email, consent_given) VALUES (?, ?)");
    stmt.run(email, consent ? 1 : 0, function (err) {
        if (err) {
            if (err.message.includes('UNIQUE constraint failed')) {
                return res.status(409).json({ error: 'Cet email est déjà inscrit.' });
            }
            console.error(err.message);
            return res.status(500).json({ error: 'Erreur serveur.' });
        }
        res.status(201).json({ message: 'Inscription réussie !', id: this.lastID });
    });
    stmt.finalize();
});

// Start Server
app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});

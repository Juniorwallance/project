const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const path = require('path');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// MongoDB connection
mongoose.connect('mongodb://localhost:27017/rugbyhighlights', {
    useNewUrlParser: true,
    useUnifiedTopology: true
});

// Highlight Schema
const highlightSchema = new mongoose.Schema({
    title: { type: String, required: true },
    description: { type: String, required: true },
    thumbnail: { type: String, required: true },
    videoUrl: { type: String, required: true },
    duration: { type: String, required: true },
    categories: [{ type: String, required: true }],
    views: { type: Number, default: 0 },
    likes: { type: Number, default: 0 },
    postedDate: { type: Date, default: Date.now },
    tags: [String],
    featured: { type: Boolean, default: false }
});

const Highlight = mongoose.model('Highlight', highlightSchema);

// User Schema for saved highlights
const userSchema = new mongoose.Schema({
    username: { type: String, required: true, unique: true },
    email: { type: String, required: true, unique: true },
    password: { type: String, required: true },
    savedHighlights: [{ type: mongoose.Schema.Types.ObjectId, ref: 'Highlight' }],
    preferences: {
        favoriteTeams: [String],
        favoriteCategories: [String]
    }
});

const User = mongoose.model('User', userSchema);

// Newsletter Schema
const newsletterSchema = new mongoose.Schema({
    email: { type: String, required: true, unique: true },
    subscribedAt: { type: Date, default: Date.now },
    active: { type: Boolean, default: true }
});

const Newsletter = mongoose.model('Newsletter', newsletterSchema);

// Authentication middleware
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ message: 'Access token required' });
    }

    jwt.verify(token, process.env.JWT_SECRET || 'your-secret-key', (err, user) => {
        if (err) {
            return res.status(403).json({ message: 'Invalid token' });
        }
        req.user = user;
        next();
    });
};

// Routes

// Get all highlights with filtering
app.get('/api/highlights', async (req, res) => {
    try {
        const { category, search, sort = 'postedDate', page = 1, limit = 12 } = req.query;
        
        let query = {};
        
        // Filter by category
        if (category && category !== 'all') {
            query.categories = category;
        }
        
        // Search in title and description
        if (search) {
            query.$or = [
                { title: { $regex: search, $options: 'i' } },
                { description: { $regex: search, $options: 'i' } },
                { tags: { $in: [new RegExp(search, 'i')] } }
            ];
        }

        const sortOptions = {};
        sortOptions[sort] = -1;

        const highlights = await Highlight.find(query)
            .sort(sortOptions)
            .limit(limit * 1)
            .skip((page - 1) * limit);

        const total = await Highlight.countDocuments(query);

        res.json({
            highlights,
            totalPages: Math.ceil(total / limit),
            currentPage: page,
            total
        });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Get single highlight
app.get('/api/highlights/:id', async (req, res) => {
    try {
        const highlight = await Highlight.findById(req.params.id);
        
        if (!highlight) {
            return res.status(404).json({ message: 'Highlight not found' });
        }

        // Increment views
        highlight.views += 1;
        await highlight.save();

        res.json(highlight);
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Get featured highlights
app.get('/api/highlights/featured', async (req, res) => {
    try {
        const featuredHighlights = await Highlight.find({ featured: true })
            .sort({ postedDate: -1 })
            .limit(6);
        
        res.json(featuredHighlights);
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Create new highlight (Admin only)
app.post('/api/highlights', authenticateToken, async (req, res) => {
    try {
        const {
            title,
            description,
            thumbnail,
            videoUrl,
            duration,
            categories,
            tags,
            featured
        } = req.body;

        const highlight = new Highlight({
            title,
            description,
            thumbnail,
            videoUrl,
            duration,
            categories: Array.isArray(categories) ? categories : [categories],
            tags: Array.isArray(tags) ? tags : [tags],
            featured: featured || false
        });

        await highlight.save();
        res.status(201).json(highlight);
    } catch (error) {
        res.status(400).json({ message: 'Error creating highlight', error: error.message });
    }
});

// Update highlight
app.put('/api/highlights/:id', authenticateToken, async (req, res) => {
    try {
        const highlight = await Highlight.findByIdAndUpdate(
            req.params.id,
            req.body,
            { new: true, runValidators: true }
        );

        if (!highlight) {
            return res.status(404).json({ message: 'Highlight not found' });
        }

        res.json(highlight);
    } catch (error) {
        res.status(400).json({ message: 'Error updating highlight', error: error.message });
    }
});

// Delete highlight
app.delete('/api/highlights/:id', authenticateToken, async (req, res) => {
    try {
        const highlight = await Highlight.findByIdAndDelete(req.params.id);

        if (!highlight) {
            return res.status(404).json({ message: 'Highlight not found' });
        }

        res.json({ message: 'Highlight deleted successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Like a highlight
app.post('/api/highlights/:id/like', authenticateToken, async (req, res) => {
    try {
        const highlight = await Highlight.findById(req.params.id);
        
        if (!highlight) {
            return res.status(404).json({ message: 'Highlight not found' });
        }

        highlight.likes += 1;
        await highlight.save();

        res.json({ likes: highlight.likes });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// User registration
app.post('/api/register', async (req, res) => {
    try {
        const { username, email, password } = req.body;

        // Check if user exists
        const existingUser = await User.findOne({ 
            $or: [{ email }, { username }] 
        });

        if (existingUser) {
            return res.status(400).json({ message: 'User already exists' });
        }

        // Hash password
        const hashedPassword = await bcrypt.hash(password, 12);

        // Create user
        const user = new User({
            username,
            email,
            password: hashedPassword
        });

        await user.save();

        // Generate token
        const token = jwt.sign(
            { userId: user._id }, 
            process.env.JWT_SECRET || 'your-secret-key',
            { expiresIn: '24h' }
        );

        res.status(201).json({
            token,
            user: {
                id: user._id,
                username: user.username,
                email: user.email
            }
        });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// User login
app.post('/api/login', async (req, res) => {
    try {
        const { email, password } = req.body;

        // Find user
        const user = await User.findOne({ email });
        if (!user) {
            return res.status(400).json({ message: 'Invalid credentials' });
        }

        // Check password
        const isMatch = await bcrypt.compare(password, user.password);
        if (!isMatch) {
            return res.status(400).json({ message: 'Invalid credentials' });
        }

        // Generate token
        const token = jwt.sign(
            { userId: user._id }, 
            process.env.JWT_SECRET || 'your-secret-key',
            { expiresIn: '24h' }
        );

        res.json({
            token,
            user: {
                id: user._id,
                username: user.username,
                email: user.email
            }
        });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Save highlight to user's saved list
app.post('/api/user/save-highlight/:id', authenticateToken, async (req, res) => {
    try {
        const user = await User.findById(req.user.userId);
        const highlightId = req.params.id;

        if (!user.savedHighlights.includes(highlightId)) {
            user.savedHighlights.push(highlightId);
            await user.save();
        }

        res.json({ message: 'Highlight saved successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Remove highlight from saved list
app.delete('/api/user/save-highlight/:id', authenticateToken, async (req, res) => {
    try {
        const user = await User.findById(req.user.userId);
        user.savedHighlights = user.savedHighlights.filter(
            id => id.toString() !== req.params.id
        );
        await user.save();

        res.json({ message: 'Highlight removed from saved list' });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Get user's saved highlights
app.get('/api/user/saved-highlights', authenticateToken, async (req, res) => {
    try {
        const user = await User.findById(req.user.userId).populate('savedHighlights');
        res.json(user.savedHighlights);
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Newsletter subscription
app.post('/api/newsletter/subscribe', async (req, res) => {
    try {
        const { email } = req.body;

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return res.status(400).json({ message: 'Invalid email address' });
        }

        // Check if already subscribed
        const existingSubscriber = await Newsletter.findOne({ email });
        if (existingSubscriber) {
            if (existingSubscriber.active) {
                return res.status(400).json({ message: 'Email already subscribed' });
            } else {
                existingSubscriber.active = true;
                await existingSubscriber.save();
                return res.json({ message: 'Resubscribed successfully' });
            }
        }

        // Create new subscription
        const subscription = new Newsletter({ email });
        await subscription.save();

        res.status(201).json({ message: 'Subscribed successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Newsletter unsubscribe
app.post('/api/newsletter/unsubscribe', async (req, res) => {
    try {
        const { email } = req.body;

        const subscription = await Newsletter.findOne({ email });
        if (!subscription) {
            return res.status(404).json({ message: 'Email not found in subscription list' });
        }

        subscription.active = false;
        await subscription.save();

        res.json({ message: 'Unsubscribed successfully' });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Get highlight categories
app.get('/api/categories', async (req, res) => {
    try {
        const categories = await Highlight.distinct('categories');
        res.json(categories);
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Get highlight statistics
app.get('/api/statistics', async (req, res) => {
    try {
        const totalHighlights = await Highlight.countDocuments();
        const totalViews = await Highlight.aggregate([
            { $group: { _id: null, total: { $sum: '$views' } } }
        ]);
        const mostViewed = await Highlight.findOne().sort({ views: -1 });
        const totalSubscribers = await Newsletter.countDocuments({ active: true });

        res.json({
            totalHighlights,
            totalViews: totalViews[0]?.total || 0,
            mostViewed: mostViewed ? {
                title: mostViewed.title,
                views: mostViewed.views
            } : null,
            totalSubscribers
        });
    } catch (error) {
        res.status(500).json({ message: 'Server error', error: error.message });
    }
});

// Serve static files in production
if (process.env.NODE_ENV === 'production') {
    app.use(express.static(path.join(__dirname, '../client/build')));

    app.get('*', (req, res) => {
        res.sendFile(path.join(__dirname, '../client/build', 'index.html'));
    });
}

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
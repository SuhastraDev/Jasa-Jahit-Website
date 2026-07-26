import './bootstrap';

import Alpine from 'alpinejs';
import { FilesetResolver, PoseLandmarker } from '@mediapipe/tasks-vision';

window.Alpine = Alpine;
window.MediaPipeVision = { FilesetResolver, PoseLandmarker };

Alpine.start();

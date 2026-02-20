import { mainscreen } from '../../components/mainscreen/';
import { grid } from '../../components/grid/';
import { actionemailinfo } from '../../components/actionemailinfo/';
const routes = [
    {
        path: '/',
        component: mainscreen,
        meta: { requiresAuth: false },
        children: [
            {
                path: '/',
                component: grid,
                name: 'grid',
                meta: { requiresAuth: false }
            },
            {
                path: '#actionemail/:id',
                component: actionemailinfo,
                name: 'actionemail',
                meta: { requiresAuth: false }
            }
        ]
    }
];

export default routes;